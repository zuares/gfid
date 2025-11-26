<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SewingReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'operator_id' => $request->get('operator_id'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
            'q' => $request->get('q'),
        ];

        $query = SewingReturn::with(['operator', 'warehouse', 'pickup'])
            ->when($filters['status'], function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['operator_id'], function ($q, $opId) {
                $q->where('operator_id', $opId);
            })
            ->when($filters['from_date'], function ($q, $from) {
                $q->whereDate('date', '>=', $from);
            })
            ->when($filters['to_date'], function ($q, $to) {
                $q->whereDate('date', '<=', $to);
            })
            ->when($filters['q'], function ($q, $search) {
                $search = trim($search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhereHas('pickup', function ($qq) use ($search) {
                            $qq->where('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('operator', function ($qq) use ($search) {
                            $qq->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('date')
            ->orderByDesc('id');

        $returns = $query->paginate(20)->withQueryString();

        // kalau mau: semua operator jahit (buat filter)
        $operators = Employee::orderBy('code')->get(); // bisa difilter role sewing nanti

        return view('production.sewing_returns.index', [
            'returns' => $returns,
            'operators' => $operators,
            'filters' => $filters,
        ]);
    }

    public function show(SewingReturn $return)
    {
        $return->load([
            'warehouse',
            'operator',
            'pickup',
            'lines.sewingPickupLine.sewingPickup',
            'lines.sewingPickupLine.bundle.finishedItem',
            'lines.sewingPickupLine.bundle.cuttingJob.lot.item',
        ]);

        return view('production.sewing_returns.show', [
            'return' => $return,
        ]);
    }

    /**
     * Form Sewing Return untuk satu Sewing Pickup
     * GET /production/sewing/returns/create?pickup_id=XX
     */
    public function create(Request $request)
    {
        $pickupId = $request->get('pickup_id') ?? old('pickup_id');

        // List pickup (untuk dropdown pilih Pickup)
        $pickups = SewingPickup::query()
            ->with('operator')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50) // kalau mau bisa diganti paginate
            ->get();

        $currentPickup = null;
        $lines = collect();

        if ($pickupId) {
            $currentPickup = SewingPickup::with([
                'operator',
                'lines.bundle.finishedItem',
                'lines.bundle.cuttingJob.lot.item',
            ])
                ->find($pickupId);

            if ($currentPickup) {
                $lines = $currentPickup->lines
                    ->map(function ($line) {
                        $qtyBundle = (float) $line->qty_bundle;
                        $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                        $returnedRej = (float) ($line->qty_returned_reject ?? 0);
                        $remaining = $qtyBundle - ($returnedOk + $returnedRej);

                        $line->remaining_qty = max($remaining, 0);

                        return $line;
                    })
                    ->filter(function ($line) {
                        return ($line->remaining_qty ?? 0) > 0;
                    })
                    ->values();
            }
        }

        // Operator yang boleh dipakai di header (QC / sewing)
        $operators = Employee::query()
            ->whereIn('role', ['sewing', 'cutting'])
            ->orderBy('code')
            ->get();

        return view('production.sewing_returns.create', [
            'pickups' => $pickups,
            'currentPickup' => $currentPickup,
            'lines' => $lines,
            'operators' => $operators,
        ]);
    }

    /**
     * Simpan Sewing Return + gerakkan inventory:
     * - OUT dari gudang sewing (WIP-SEW / sesuai pickup->warehouse_id)
     * - IN ke WIP-FIN (OK)
     * - IN ke REJECT (Reject)
     */
    public function store(Request $request)
    {
        // 1. VALIDASI DASAR
        $data = $request->validate([
            'pickup_id' => ['required', 'exists:sewing_pickups,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'operator_id' => ['nullable', 'exists:employees,id'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.line_id' => ['required', 'exists:sewing_pickup_lines,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string'],
        ]);

        $pickup = SewingPickup::with(['lines.bundle.finishedItem'])->findOrFail($data['pickup_id']);

        // Siapkan gudang WIP-FIN (tujuan OK)
        $wipFinWarehouse = Warehouse::where('code', 'WIP-FIN')->first();
        if (!$wipFinWarehouse) {
            throw ValidationException::withMessages([
                'pickup_id' => 'Gudang WIP-FIN belum diset di master gudang.',
            ]);
        }

        $return = null;

        DB::transaction(function () use (&$return, $data, $pickup, $wipFinWarehouse) {
            // ===========================
            // 2. GENERATE KODE SWR-YYYYMMDD-###
            // ===========================
            $date = Carbon::parse($data['date'] ?? now());
            $prefix = 'SWR-' . $date->format('Ymd') . '-';

            $lastCode = SewingReturn::whereDate('date', $date->toDateString())
                ->where('code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $nextNumber = 1;
            if ($lastCode && preg_match('/(\d+)$/', $lastCode, $m)) {
                $nextNumber = (int) $m[1] + 1;
            }

            $code = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // 3. BUAT HEADER RETURN
            $return = SewingReturn::create([
                'code' => $code,
                'pickup_id' => $pickup->id,
                'warehouse_id' => $pickup->warehouse_id, // asal stok = WIP-SEW
                'operator_id' => $data['operator_id'] ?? $pickup->operator_id,
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'posted',
            ]);

            $adaBaris = false;

            foreach ($data['results'] as $idx => $row) {
                /** @var SewingPickupLine $line */
                $line = SewingPickupLine::lockForUpdate()
                    ->with('bundle.finishedItem')
                    ->findOrFail($row['line_id']);

                $bundle = $line->bundle;
                $item = $bundle?->finishedItem;

                if (!$item) {
                    throw ValidationException::withMessages([
                        "results.$idx.qty_ok" => "Item jadi untuk bundle ini belum di-set.",
                    ]);
                }

                $qtyBundle = (float) $line->qty_bundle;
                $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                $returnedRej = (float) ($line->qty_returned_reject ?? 0);
                $remaining = $qtyBundle - ($returnedOk + $returnedRej);

                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                // kalau user gak isi apa-apa → lewati baris ini
                if ($qtyOk <= 0 && $qtyReject <= 0) {
                    continue;
                }

                // 4. VALIDASI SISA
                if ($remaining <= 0) {
                    throw ValidationException::withMessages([
                        "results.$idx.qty_ok" => "Qty sisa bundle sudah 0, tidak bisa setor lagi.",
                    ]);
                }

                if (($qtyOk + $qtyReject) - $remaining > 0.000001) {
                    $max = number_format($remaining, 2, ',', '.');
                    throw ValidationException::withMessages([
                        "results.$idx.qty_ok" => "Qty OK + Reject melebihi qty sisa (maks $max).",
                    ]);
                }

                // (opsional) wajib catatan kalau ada reject
                if ($qtyReject > 0 && empty($row['notes'])) {
                    throw ValidationException::withMessages([
                        "results.$idx.notes" => "Harap isi catatan jika ada qty reject.",
                    ]);
                }

                $adaBaris = true;

                // 5. SIMPAN DETAIL RETURN
                $returnLine = SewingReturnLine::create([
                    'sewing_return_id' => $return->id,
                    'sewing_pickup_line_id' => $line->id,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'notes' => $row['notes'] ?? null,
                ]);

                // 6. UPDATE AKUMULASI DI LINE PICKUP
                $line->qty_returned_ok = $returnedOk + $qtyOk;
                $line->qty_returned_reject = $returnedRej + $qtyReject;

                $totalReturned = $line->qty_returned_ok + $line->qty_returned_reject;

                if ($totalReturned >= $qtyBundle - 0.000001) {
                    $line->status = 'done';
                } else {
                    $line->status = 'in_progress';
                }

                $line->save();

                // 7. MUTASI STOK
                $totalProcessed = $qtyOk + $qtyReject;

                if ($totalProcessed > 0) {
                    // ⬇️ STOCK OUT dari gudang pickup (WIP-SEW)
                    $this->inventory->stockOut(
                        warehouseId: $pickup->warehouse_id,
                        itemId: $item->id,
                        qty: $totalProcessed,
                        date: $data['date'] ?? now(),
                        sourceType: 'sewing_returns',
                        sourceId: $returnLine->id,
                        notes: "Keluar dari WIP-SEW (OK {$qtyOk}, Reject {$qtyReject})",
                        allowNegative: false,
                        lotId: null,
                    );
                }

                if ($qtyOk > 0) {
                    // ⬆️ STOCK IN ke WIP-FIN untuk hasil OK
                    $this->inventory->stockIn(
                        warehouseId: $wipFinWarehouse->id,
                        itemId: $item->id,
                        qty: $qtyOk,
                        date: $data['date'] ?? now(),
                        sourceType: 'sewing_returns',
                        sourceId: $returnLine->id,
                        notes: 'Masuk WIP-FIN (hasil jahit OK)',
                        lotId: null,
                    );

                    // 🔁 UPDATE WIP-FIN DI CUTTING_JOB_BUNDLES
                    //    bundle sekarang punya saldo WIP di WIP-FIN sebesar qtyOk tambahan
                    $currentWipQty = (float) ($bundle->wip_qty ?? 0);

                    $bundle->wip_warehouse_id = $wipFinWarehouse->id;
                    $bundle->wip_qty = $currentWipQty + $qtyOk;

                    $bundle->save();
                }

                // Qty Reject tidak dimasukkan ke gudang lain → dianggap loss
                // (kalau nanti mau masuk REJECT warehouse, kita bisa tambah block di sini)
            }

            if (!$adaBaris) {
                throw ValidationException::withMessages([
                    'results' => 'Isi minimal satu baris Qty OK / Reject.',
                ]);
            }

            // 8. UPDATE STATUS HEADER PICKUP
            $stillInProgress = $pickup->lines()
                ->where('status', 'in_progress')
                ->exists();

            $pickup->status = $stillInProgress ? 'posted' : 'closed';
            $pickup->save();
        });

        return redirect()
            ->route('production.sewing_returns.show', $return)
            ->with('success', 'Sewing Return + mutasi stok berhasil disimpan.');
    }

    public function operatorSummary(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $operatorId = $request->get('operator_id');

        $q = SewingReturn::query()
            ->join('sewing_return_lines as l', 'l.sewing_return_id', '=', 'sewing_returns.id')
            ->join('employees as e', 'e.id', '=', 'sewing_returns.operator_id')
            ->selectRaw('
            sewing_returns.operator_id,
            e.code as operator_code,
            e.name as operator_name,
            SUM(l.qty_ok) as total_ok,
            SUM(l.qty_reject) as total_reject,
            COUNT(DISTINCT sewing_returns.id) as total_returns,
            COUNT(l.id) as total_lines
        ')
            ->groupBy('sewing_returns.operator_id', 'e.code', 'e.name');

        if ($dateFrom) {
            $q->whereDate('sewing_returns.date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $q->whereDate('sewing_returns.date', '<=', $dateTo);
        }

        if ($operatorId) {
            $q->where('sewing_returns.operator_id', $operatorId);
        }

        $rows = $q->orderBy('operator_code')->get();

        // list operator buat filter dropdown
        $operators = Employee::query()
            ->where('role', 'sewing')
            ->orderBy('code')
            ->get();

        return view('production.sewing_returns.report_operators', [
            'rows' => $rows,
            'operators' => $operators,
            'filters' => $request->only(['date_from', 'date_to', 'operator_id']),
        ]);
    }

}
