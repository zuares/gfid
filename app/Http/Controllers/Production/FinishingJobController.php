<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinishingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {
    }

    /**
     * Index Finishing Job.
     */

    public function index(Request $request)
    {
        $status = $request->input('status'); // draft / posted / null (semua)
        $search = $request->input('search'); // cari kode / catatan
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = FinishingJob::query()
            ->with(['createdBy'])
            ->withCount('lines');

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $jobs = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.finishing_jobs.index', [
            'jobs' => $jobs,
            'status' => $status,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Bundles Ready for Finishing: bundle yang WIP-nya sedang di WIP-FIN dan wip_qty > 0.
     */
    public function readyBundles(Request $request): View
    {
        // 1. Cari warehouse WIP-FIN (sekalian objeknya biar bisa dipakai di view)
        $wipFinWarehouse = Warehouse::query()
            ->where('code', 'WIP-FIN') // pastikan code di tabel warehouses = 'WIP-FIN'
            ->first();

        // Kalau warehouse WIP-FIN belum ada, return kosong
        if (!$wipFinWarehouse) {
            $bundles = collect();
            $totalBundles = 0;
            $totalWipQty = 0;

            return view('production.finishing_jobs.bundles_ready', compact(
                'bundles',
                'totalBundles',
                'totalWipQty',
                'wipFinWarehouse'
            ));
        }

        // 2. Base query: bundle yang WIP-nya di WIP-FIN dan ada saldo
        $query = CuttingJobBundle::query()
            ->with([
                'cuttingJob',
                'lot.item',
                'finishedItem',
                'wipWarehouse',
            ])
            ->where('wip_warehouse_id', $wipFinWarehouse->id)
            ->where('wip_qty', '>', 0.0001);

        // 3. FILTER optional

        // Filter item_id via lot.item
        if ($itemId = $request->input('item_id')) {
            $query->whereHas('lot', function ($q) use ($itemId) {
                $q->where('item_id', $itemId);
            });
        }

        // Filter warna (color) via lot.item.color
        if ($color = $request->input('color')) {
            $query->whereHas('lot.item', function ($q) use ($color) {
                $q->where('color', 'like', '%' . $color . '%');
            });
        }

        // Filter kode bundle
        if ($bundleCode = $request->input('bundle_code')) {
            $query->where('bundle_code', 'like', '%' . $bundleCode . '%');
        }

        // (opsional) filter search umum (kode job / kode item)
        if ($q = $request->input('q')) {
            $q = trim($q);
            $query->where(function ($sub) use ($q) {
                $sub->where('bundle_code', 'like', "%{$q}%")
                    ->orWhereHas('cuttingJob', function ($qq) use ($q) {
                        $qq->where('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('lot.item', function ($qqq) use ($q) {
                        $qqq->where('code', 'like', "%{$q}%")
                            ->orWhere('name', 'like', "%{$q}%");
                    });
            });
        }

        // 4. Summary (total bundle & total WIP-FIN untuk header)
        $summaryQuery = clone $query;
        $totalBundles = (clone $summaryQuery)->count();
        $totalWipQty = (clone $summaryQuery)->sum('wip_qty');

        // 5. Paginate (urut per cutting job + bundle_no biar logis)
        $bundles = $query
            ->orderBy('cutting_job_id')
            ->orderBy('bundle_no')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // 6. Kirim ke view
        return view('production.finishing_jobs.bundles_ready', compact(
            'bundles',
            'totalBundles',
            'totalWipQty',
            'wipFinWarehouse'
        ));
    }

    /**
     * Form create Finishing Job.
     * Bisa menerima bundle_ids[] dari Bundles Ready for Finishing.
     */
    public function create(Request $request): View
    {
        $date = Carbon::today()->toDateString();

        // Operator finishing (bisa disaring role kalau mau)
        $operators = Employee::query()
            ->orderBy('name')
            ->get();

        // Ambil semua bundle yang punya saldo WIP-FIN > 0
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        $bundlesQuery = CuttingJobBundle::query()
            ->with(['cuttingJob', 'lot.item', 'finishedItem'])
            ->when($wipFinWarehouseId, function ($q) use ($wipFinWarehouseId) {
                $q->where('wip_warehouse_id', $wipFinWarehouseId)
                    ->where('wip_qty', '>', 0.0001);
            })
            ->orderBy('cutting_job_id')
            ->orderBy('bundle_no');

        // Semua bundle ready → untuk dropdown
        $bundles = $bundlesQuery->get();

        // bundle_ids[] dari Bundles Ready
        $bundleIds = (array) $request->input('bundle_ids', []);

        $lines = [];

        if (!empty($bundleIds)) {
            // subset yang dipilih
            $initialBundles = $bundles->whereIn('id', $bundleIds);

            foreach ($initialBundles as $bundle) {
                $itemModel = $bundle->finishedItem ?? $bundle->lot?->item;
                $itemId = $bundle->finished_item_id ?? $bundle->lot?->item_id ?? null;

                // label item
                $itemLabel = $itemModel
                ? trim(
                    ($itemModel->code ?? '') . ' — ' .
                    ($itemModel->name ?? '') . ' ' .
                    ($itemModel->color ?? '')
                )
                : '';

                // jumlah yang sudah setor jahit OK → wip_qty
                $alreadyReturnedOk = (float) ($bundle->wip_qty ?? 0);

                // build line default
                $lines[] = [
                    'bundle_id' => $bundle->id,
                    'item_id' => $itemId,
                    'item_label' => $itemLabel,

                    // qty_in = hanya yang sudah setor OK (saldo WIP-FIN)
                    'qty_in' => $alreadyReturnedOk,

                    // finishing OK default ikut qty_in
                    'qty_ok' => $alreadyReturnedOk,

                    // default reject = 0
                    'qty_reject' => 0,

                    'wip_balance' => $alreadyReturnedOk,
                    'operator_id' => null,
                    'reject_reason' => null,
                ];
            }
        }

        return view('production.finishing_jobs.create', compact(
            'date',
            'operators',
            'bundles',
            'lines',
        ));
    }

    /**
     * Simpan draft Finishing Job.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],

            'lines.*.bundle_id' => ['required', 'integer', 'exists:cutting_job_bundles,id'],
            'lines.*.operator_id' => ['nullable', 'integer', 'exists:employees,id'],
            'lines.*.qty_in' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_ok' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'lines.*.reject_notes' => ['nullable', 'string'],
        ]);

        $code = CodeGenerator::generate('FIN');

        $job = FinishingJob::create([
            'code' => $code,
            'date' => $validated['date'],
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        foreach ($validated['lines'] as $lineData) {
            $qtyIn = (float) $lineData['qty_in'];
            $qtyOk = (float) $lineData['qty_ok'];
            $qtyReject = (float) $lineData['qty_reject'];

            if ($qtyOk + $qtyReject > $qtyIn + 0.0001) {
                return back()
                    ->withInput()
                    ->withErrors(['lines' => 'Qty OK + Reject tidak boleh melebihi Qty In.']);
            }

            /** @var CuttingJobBundle $bundle */
            $bundle = CuttingJobBundle::query()
                ->with(['finishedItem', 'lot.item'])
                ->findOrFail($lineData['bundle_id']);

            $wipBalance = (float) ($bundle->wip_qty ?? 0);
            if ($qtyIn > $wipBalance + 0.0001) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'lines' => 'Qty In untuk bundle ' . ($bundle->bundle_code ?? $bundle->id)
                        . ' melebihi saldo WIP-FIN (' . $wipBalance . ').',
                    ]);
            }

            // Finishing WAJIB pakai finished_item_id, jangan fallback ke lot item
            $itemId = $bundle->finished_item_id;

            if (!$itemId) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'lines' => 'Item finishing untuk bundle '
                        . ($bundle->bundle_code ?? $bundle->id)
                        . ' belum di-set (finished_item_id kosong). Silakan perbaiki Cutting Job / Bundle.',
                    ]);
            }

            FinishingJobLine::create([
                'finishing_job_id' => $job->id,
                'bundle_id' => $bundle->id,
                'operator_id' => $lineData['operator_id'] ?? null,
                'item_id' => $itemId, // Pasti finished item (misal K7BLK)
                'qty_in' => $qtyIn,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'reject_reason' => $lineData['reject_reason'] ?? null,
                'reject_notes' => $lineData['reject_notes'] ?? null,
                'processed_at' => $validated['date'],
            ]);
        }

        return redirect()
            ->route('production.finishing_jobs.show', $job)
            ->with('status', 'Finishing Job berhasil dibuat sebagai draft.');
    }

    /**
     * Show Finishing Job.
     */
    public function show(FinishingJob $finishing_job): View
    {
        $finishing_job->load([
            'lines.bundle.cuttingJob',
            'lines.bundle.lot.item',
            'lines.bundle.finishedItem',
            'lines.operator',
            'createdBy',
        ]);

        return view('production.finishing_jobs.show', [
            'job' => $finishing_job,
        ]);
    }

    /**
     * Edit draft (header saja dulu).
     */
    public function edit(FinishingJob $finishing_job): View
    {
        if ($finishing_job->isPosted()) {
            abort(403, 'Finishing Job yang sudah posted tidak bisa di-edit.');
        }

        $finishing_job->load([
            'lines.bundle.lot.item',
            'lines.bundle.finishedItem',
            'lines.operator',
        ]);

        $operators = Employee::orderBy('name')->get();

        return view('production.finishing_jobs.edit', [
            'job' => $finishing_job,
            'operators' => $operators,
        ]);
    }

    public function update(Request $request, FinishingJob $finishing_job): RedirectResponse
    {
        if ($finishing_job->isPosted()) {
            abort(403, 'Finishing Job yang sudah posted tidak bisa di-update.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $finishing_job->update([
            'date' => $data['date'],
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('production.finishing_jobs.show', $finishing_job)
            ->with('status', 'Header Finishing Job berhasil diperbarui.');
    }

    /**
     * Posting Finishing Job:
     *  - WIP-FIN → FG + REJECT
     *  - update wip_qty bundle (INI YANG BIKIN BUNDLE HILANG DARI READY LIST)
     */
    public function post(FinishingJob $finishing_job): RedirectResponse
    {
        // Pakai alias $job supaya lebih enak dibaca
        $job = $finishing_job;

        if (!$job || !$job->id) {
            return redirect()
                ->route('production.finishing_jobs.index')
                ->withErrors(['finishing_job' => 'Finishing Job tidak ditemukan.']);
        }

        // 1. Hanya boleh posting kalau masih draft
        if ($job->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', ['finishing_job' => $job->id])
                ->with('status', 'Finishing Job ini sudah diposting sebelumnya.');
        }

        // 2. Pastikan warehouse WIP-FIN, FG, REJECT ada
        $requiredCodes = ['WIP-FIN', 'FG', 'REJECT'];

        $warehouses = Warehouse::query()
            ->whereIn('code', $requiredCodes)
            ->get()
            ->keyBy('code');

        $missing = array_diff($requiredCodes, $warehouses->keys()->all());

        if (!empty($missing)) {
            return back()->withErrors([
                'warehouse' => 'Warehouse berikut belum dikonfigurasi: '
                . implode(', ', $missing)
                . '. Silakan setting dulu di Master Gudang.',
            ]);
        }

        $wipFinWarehouseId = $warehouses['WIP-FIN']->id;
        $fgWarehouseId = $warehouses['FG']->id;
        $rejectWarehouseId = $warehouses['REJECT']->id;

        // 3. Siapkan tanggal mutasi (pakai tanggal finishing job)
        $date = $job->date instanceof \DateTimeInterface
        ? $job->date
        : Carbon::parse($job->date);

        // 4. Load relasi supaya nggak N+1
        $job->load(['lines', 'lines.bundle']);

        DB::transaction(function () use ($job, $wipFinWarehouseId, $fgWarehouseId, $rejectWarehouseId, $date) {
            foreach ($job->lines as $line) {
                if (
                    ($line->qty_in ?? 0) <= 0
                    && ($line->qty_ok ?? 0) <= 0
                    && ($line->qty_reject ?? 0) <= 0
                ) {
                    continue;
                }

                // 1) STOCK OUT dari WIP-FIN (qty_in)
                if ($line->qty_in > 0) {
                    $this->inventory->stockOut(
                        $wipFinWarehouseId,
                        $line->item_id,
                        $line->qty_in,
                        $date,
                        FinishingJob::class,
                        $job->id,
                        'Finishing ' . $job->code,
                        false,
                        null
                    );
                }

                // 2) STOCK IN ke FG (qty OK)
                if ($line->qty_ok > 0) {
                    $this->inventory->stockIn(
                        $fgWarehouseId,
                        $line->item_id,
                        $line->qty_ok,
                        $date,
                        FinishingJob::class,
                        $job->id,
                        'Finishing OK ' . $job->code,
                        false,
                        null
                    );
                }

                // 3) STOCK IN ke REJECT (qty reject)
                if ($line->qty_reject > 0) {
                    $this->inventory->stockIn(
                        $rejectWarehouseId,
                        $line->item_id,
                        $line->qty_reject,
                        $date,
                        FinishingJob::class,
                        $job->id,
                        'Finishing REJECT ' . $job->code,
                        false,
                        null
                    );
                }

                // 4) KURANGI SALDO WIP-FIN DI BUNDLE
                //    Supaya bundle hilang (atau berkurang) dari daftar Ready Bundles
                if ($line->qty_in > 0 && $line->bundle) {
                    $bundle = $line->bundle;
                    $current = (float) ($bundle->wip_qty ?? 0);
                    $used = (float) $line->qty_in;

                    $newWipQty = $current - $used;

                    if ($newWipQty < 0 && abs($newWipQty) > 0.0001) {
                        // Kalau sampai minus, clamp jadi 0 saja biar aman
                        $newWipQty = 0;
                    }

                    // Normalisasi epsilon kecil
                    if ($newWipQty < 0.0001) {
                        $newWipQty = 0;
                    }

                    $bundle->wip_qty = $newWipQty;

                    // Opsional: kalau sudah 0, bisa juga kosongkan lokasinya
                    // supaya secara logis tidak dianggap lagi ada di WIP-FIN
                    if ($newWipQty === 0.0) {
                        // $bundle->wip_warehouse_id = null;
                        // kalau mau tetap tahu history lokasi terakhir, biarkan saja
                    }

                    $bundle->save();
                }
            }

            // Update status job jadi posted
            $job->update([
                'status' => 'posted',
                'posted_at' => now(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('production.finishing_jobs.show', ['finishing_job' => $job->id])
            ->with('status', 'Finishing Job berhasil diposting dan stok sudah diperbarui.');
    }
}
