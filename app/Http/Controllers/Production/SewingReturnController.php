<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SewingReturnController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request)
    {
        $q = SewingReturn::query()
            ->with([
                'operator',
                'warehouse',
                'lines.pickupLine.pickup.warehouse',
                'lines.pickupLine.bundle.finishedItem',
                'lines.pickupLine.bundle.cuttingJob.lot.item',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id');

        // optional filter status
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $returns = $q->paginate(20)->withQueryString();

        return view('production.sewing_returns.index', [
            'returns' => $returns,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(SewingReturn $sewingReturn)
    {
        $sewingReturn->load([
            'operator',
            'warehouse',
            'lines.pickupLine.pickup.warehouse',
            'lines.pickupLine.bundle.finishedItem',
            'lines.pickupLine.bundle.cuttingJob.lot.item',
        ]);

        // sementara untuk cek, boleh aktifkan:
        dd($sewingReturn->lines->toArray());

        return view('production.sewing_returns.show', [
            'return' => $sewingReturn,
        ]);
    }

    /**
     * Form Sewing Return untuk satu Sewing Pickup
     * GET /production/sewing/returns/create?pickup_id=XX
     */
    public function create(Request $request)
    {
        $pickupId = $request->get('pickup_id');

        /** @var SewingPickup $pickup */
        $pickup = SewingPickup::with([
            'warehouse',
            'operator',
            'lines.bundle.finishedItem',
            'lines.bundle.cuttingJob.lot.item',
        ])
            ->findOrFail($pickupId);

        // Hanya lines yang masih in_progress
        $lines = $pickup->lines()
            ->where('status', 'in_progress')
            ->get();

        if ($lines->isEmpty()) {
            return redirect()
                ->route('production.sewing_pickups.show', $pickup)
                ->with('warning', 'Semua bundle pada pickup ini sudah selesai dikembalikan.');
        }

        return view('production.sewing_returns.create', [
            'pickup' => $pickup,
            'lines' => $lines,
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
        $validated = $request->validate([
            'pickup_id' => ['required', 'exists:sewing_pickups,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.line_id' => ['required', 'exists:sewing_pickup_lines,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string'],
        ], [
            'results.required' => 'Minimal satu baris hasil jahit harus diisi.',
            'results.*.line_id.required' => 'Baris bundle tidak valid.',
        ]);

        DB::transaction(function () use ($validated) {

            /** @var SewingPickup $pickup */
            $pickup = SewingPickup::with([
                'warehouse',
                'operator',
            ])
                ->findOrFail($validated['pickup_id']);

            $date = $validated['date'];

            // Cari gudang tujuan: WIP-FIN & REJECT
            $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
            $rejectWarehouseId = Warehouse::where('code', 'REJECT')->value('id'); // sesuaikan kodenya

            if (!$wipFinWarehouseId) {
                throw ValidationException::withMessages([
                    'pickup_id' => 'Gudang WIP-FIN belum dikonfigurasi. Pastikan ada warehouse dengan code "WIP-FIN".',
                ]);
            }

            if (!$rejectWarehouseId) {
                throw ValidationException::withMessages([
                    'pickup_id' => 'Gudang REJECT belum dikonfigurasi. Pastikan ada warehouse dengan code "REJECT".',
                ]);
            }

            // Header Sewing Return
            /** @var SewingReturn $return */
            $return = SewingReturn::create([
                'code' => CodeGenerator::generate('SWR'),
                'date' => $date,
                'warehouse_id' => $pickup->warehouse_id, // 🔹 penting buat show header
                'operator_id' => $pickup->operator_id,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            $processedLines = 0;

            foreach ($validated['results'] as $row) {
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);
                $totalReturn = $qtyOk + $qtyReject;

                if ($totalReturn <= 0) {
                    continue;
                }

                /** @var SewingPickupLine $line */
                $line = SewingPickupLine::with([
                    'bundle.finishedItem',
                    'bundle.cuttingJob.lot',
                ])
                    ->findOrFail($row['line_id']);

                $bundle = $line->bundle;
                $lot = $bundle?->cuttingJob?->lot;
                $lotId = $lot?->id;

                // Cek sisa yang masih boleh direturn:
                $alreadyReturned = (float) ($line->qty_returned_ok + $line->qty_returned_reject);
                $remaining = (float) ($line->qty_bundle - $alreadyReturned);

                if ($remaining <= 0) {
                    continue;
                }

                // Clamp: jangan boleh lebih dari remaining
                if ($totalReturn > $remaining) {
                    // scale down proporsional → atau simpel: batasi qty_ok dulu
                    $excess = $totalReturn - $remaining;

                    if ($qtyReject >= $excess) {
                        $qtyReject -= $excess;
                    } else {
                        $excess -= $qtyReject;
                        $qtyReject = 0;
                        $qtyOk = max(0, $qtyOk - $excess);
                    }

                    $totalReturn = $qtyOk + $qtyReject;
                    if ($totalReturn <= 0) {
                        continue;
                    }
                }

                // 🔹 Simpan SewingReturnLine
                SewingReturnLine::create([
                    'sewing_return_id' => $return->id,
                    'sewing_pickup_line_id' => $line->id,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'notes' => $row['notes'] ?? null,
                ]);

                // 🔹 INVENTORY:
                // OUT dari gudang sewing (pickup->warehouse_id)
                $notes = "Sewing return {$return->code} - bundle {$bundle->bundle_code}";

                $this->inventory->stockOut(
                    warehouseId: $pickup->warehouse_id,
                    itemId: $bundle->finished_item_id,
                    qty: $totalReturn,
                    date: $date,
                    sourceType: 'sewing_return',
                    sourceId: $return->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: $lotId,
                );

                // IN ke WIP-FIN (OK)
                if ($qtyOk > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $wipFinWarehouseId,
                        itemId: $bundle->finished_item_id,
                        qty: $qtyOk,
                        date: $date,
                        sourceType: 'sewing_return_ok',
                        sourceId: $return->id,
                        notes: $notes,
                        lotId: $lotId,
                        unitCost: null, // ikut moving average LOT
                    );
                }

                // IN ke REJECT (Reject)
                if ($qtyReject > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $rejectWarehouseId,
                        itemId: $bundle->finished_item_id,
                        qty: $qtyReject,
                        date: $date,
                        sourceType: 'sewing_return_reject',
                        sourceId: $return->id,
                        notes: $notes,
                        lotId: $lotId,
                        unitCost: null,
                    );
                }

                // 🔹 Update progress line
                $line->qty_returned_ok = (float) $line->qty_returned_ok + $qtyOk;
                $line->qty_returned_reject = (float) $line->qty_returned_reject + $qtyReject;

                if ($line->qty_returned_ok + $line->qty_returned_reject >= $line->qty_bundle) {
                    $line->status = 'done';
                }

                $line->save();

                $processedLines++;
            }

            if ($processedLines === 0) {
                throw ValidationException::withMessages([
                    'results' => 'Tidak ada baris Sewing Return yang valid. Pastikan Qty OK/Reject diisi dan tidak melebihi sisa.',
                ]);
            }

            // Optional: kalau semua line pickup sudah done → tutup pickup
            $stillInProgress = $pickup->lines()
                ->where('status', 'in_progress')
                ->exists();

            if (!$stillInProgress) {
                $pickup->status = 'closed';
                $pickup->save();
            }
        });

        return redirect()
            ->route('production.sewing_pickups.index')
            ->with('success', 'Sewing return berhasil disimpan dan stok sudah dipindahkan ke WIP-FIN / REJECT.');
    }
}
