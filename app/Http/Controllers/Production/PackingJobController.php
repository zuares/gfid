<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\PackingJob;
use App\Models\PackingJobLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PackingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {
    }

    /**
     * Index Packing Job.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status'); // draft / posted / null
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = PackingJob::query()
            ->with(['createdBy'])
            ->withSum('lines as total_packed', 'qty_packed');

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
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $jobs = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.packing_jobs.index', [
            'jobs' => $jobs,
            'status' => $status,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * List item dengan stok di FG (opsional, untuk browsing).
     */
    public function readyItems(Request $request): View
    {
        // Cari gudang FG
        $fgWarehouse = Warehouse::query()
            ->where('code', 'FG')
            ->first();

        $stocks = collect();

        if ($fgWarehouse) {
            $query = InventoryStock::query()
                ->with('item')
                ->where('warehouse_id', $fgWarehouse->id)
                ->where('qty', '>', 0.0001);

            if ($q = $request->input('q')) {
                $q = trim($q);
                $query->whereHas('item', function ($sub) use ($q) {
                    $sub->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('color', 'like', "%{$q}%");
                });
            }

            $stocks = $query
                ->orderBy('item_id')
                ->paginate(20)
                ->withQueryString();
        }

        return view('production.packing_jobs.fg_ready', [
            'stocks' => $stocks,
            'fgWarehouse' => $fgWarehouse,
        ]);
    }

    /**
     * Form create Packing Job.
     */
    public function create(Request $request): View
    {
        $date = Carbon::today()->toDateString();

        $fgWarehouseId = Warehouse::where('code', 'FG')->value('id');

        $stocksQuery = InventoryStock::query()
            ->with('item')
            ->when($fgWarehouseId, function ($q) use ($fgWarehouseId) {
                $q->where('warehouse_id', $fgWarehouseId)
                    ->where('qty', '>', 0.0001);
            })
            ->orderBy('item_id');

        $stocks = $stocksQuery->get();

        $itemIds = (array) $request->input('item_ids', []);

        $lines = [];

        if (!empty($itemIds)) {
            $initialStocks = $stocks->whereIn('item_id', $itemIds);

            foreach ($initialStocks as $stock) {
                $item = $stock->item;

                $label = $item
                ? trim(
                    ($item->code ?? '') . ' — ' .
                    ($item->name ?? '') . ' ' .
                    ($item->color ?? '')
                )
                : 'ITEM-' . $stock->item_id;

                $fgBalance = (float) $stock->qty;

                $lines[] = [
                    'item_id' => $stock->item_id,
                    'item_label' => $label,
                    'fg_balance' => $fgBalance,
                    'qty_packed' => $fgBalance, // default: pack semua, boleh diubah user
                    'notes' => null,
                ];
            }
        }

        return view('production.packing_jobs.create', [
            'date' => $date,
            'stocks' => $stocks,
            'lines' => $lines,
        ]);
    }

    /**
     * Simpan draft Packing Job.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi dasar
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_packed' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        // 2. Pastikan warehouse FG & PACKED ada
        $warehouses = Warehouse::query()
            ->whereIn('code', ['FG', 'PACKED'])
            ->get()
            ->keyBy('code');

        if (!isset($warehouses['FG'], $warehouses['PACKED'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'warehouse' => 'Warehouse FG dan PACKED belum dikonfigurasi.',
                ]);
        }

        $fgWarehouseId = $warehouses['FG']->id;
        $packedWarehouseId = $warehouses['PACKED']->id;

        $normalizedLines = [];

        // 3. Normalisasi & validasi per baris (server-side)
        foreach ($validated['lines'] as $index => $lineData) {
            $itemId = (int) $lineData['item_id'];
            $qtyPacked = (float) $lineData['qty_packed'];

            if ($qtyPacked < 0) {
                $qtyPacked = 0;
            }

            if ($qtyPacked <= 0) {
                continue;
            }

            // Cek saldo FG real-time
            $fgBalance = (float) (
                InventoryStock::query()
                    ->where('warehouse_id', $fgWarehouseId)
                    ->where('item_id', $itemId)
                    ->value('qty') ?? 0
            );

            if ($qtyPacked > $fgBalance + 0.0001) {
                $item = Item::find($itemId);

                $itemLabel = $item
                ? trim(($item->code ?? '') . ' — ' . ($item->name ?? '') . ' ' . ($item->color ?? ''))
                : ('ID ' . $itemId);

                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.qty_packed" =>
                        'Qty Packing untuk item ' . $itemLabel .
                        ' melebihi saldo FG (' . $fgBalance . ').',
                    ]);
            }

            $normalizedLines[] = [
                'item_id' => $itemId,
                'qty_fg' => $fgBalance,
                'qty_packed' => $qtyPacked,
                'notes' => $lineData['notes'] ?? null,
            ];
        }

        if (empty($normalizedLines)) {
            return back()
                ->withInput()
                ->withErrors([
                    'lines' => 'Minimal harus ada 1 baris dengan Qty Packed > 0.',
                ]);
        }

        // 4. Simpan header + detail dalam transaksi
        $job = DB::transaction(function () use ($validated, $normalizedLines, $request, $fgWarehouseId, $packedWarehouseId) {
            $code = CodeGenerator::generate('PCK');

            /** @var \App\Models\PackingJob $job */
            $job = PackingJob::create([
                'code' => $code,
                'date' => $validated['date'],
                'status' => 'draft',
                'channel' => $validated['channel'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'warehouse_from_id' => $fgWarehouseId,
                'warehouse_to_id' => $packedWarehouseId,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($normalizedLines as $line) {
                PackingJobLine::create([
                    'packing_job_id' => $job->id,
                    'item_id' => $line['item_id'],
                    'qty_fg' => $line['qty_fg'],
                    'qty_packed' => $line['qty_packed'],
                    'packed_at' => $validated['date'], // kalau kamu punya kolom ini
                    'notes' => $line['notes'],
                ]);
            }

            return $job;
        });

        return redirect()
            ->route('production.packing_jobs.show', $job->id)
            ->with('status', 'Packing Job berhasil dibuat sebagai draft.');
    }

    /**
     * Show Packing Job.
     */
    public function show(PackingJob $packing_job): View
    {
        $packing_job->load([
            'lines.item',
            'createdBy',
            'warehouseFrom',
            'warehouseTo',
        ]);

        return view('production.packing_jobs.show', [
            'job' => $packing_job,
        ]);
    }

    /**
     * Edit draft.
     */
    public function edit(PackingJob $packing_job): View | RedirectResponse
    {
        $job = $packing_job;

        if ($job->status !== 'draft') {
            return redirect()
                ->route('production.packing_jobs.show', $job)
                ->with('status', 'Packing Job sudah diposting dan tidak bisa diedit.');
        }

        $date = $job->date instanceof \DateTimeInterface
        ? $job->date->format('Y-m-d')
        : Carbon::parse($job->date)->format('Y-m-d');

        $fgWarehouseId = $job->warehouse_from_id ?: Warehouse::where('code', 'FG')->value('id');

        $stocks = InventoryStock::query()
            ->with('item')
            ->when($fgWarehouseId, function ($q) use ($fgWarehouseId) {
                $q->where('warehouse_id', $fgWarehouseId)
                    ->where('qty', '>', 0.0001);
            })
            ->orderBy('item_id')
            ->get();

        $lines = $job->lines->map(function (PackingJobLine $line) {
            $item = $line->item;
            $label = $item
            ? trim(($item->code ?? '') . ' — ' . ($item->name ?? '') . ' ' . ($item->color ?? ''))
            : '';

            return [
                'item_id' => $item?->id,
                'item_label' => $label,
                'fg_balance' => $line->qty_fg,
                'qty_fg' => $line->qty_fg,
                'qty_packed' => $line->qty_packed,
                'notes' => $line->notes,
            ];
        })->values()->all();

        return view('production.packing_jobs.create', [
            'job' => $job,
            'date' => $date,
            'stocks' => $stocks,
            'lines' => $lines,
        ]);
    }

    /**
     * Update draft Packing Job.
     */
    public function update(Request $request, PackingJob $packing_job): RedirectResponse
    {
        $job = $packing_job;

        if ($job->status !== 'draft') {
            abort(400, 'Hanya Packing Job dengan status draft yang dapat diedit.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_packed' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $fgWarehouseId = $job->warehouse_from_id ?: Warehouse::where('code', 'FG')->value('id');
        if (!$fgWarehouseId) {
            return back()
                ->withInput()
                ->withErrors(['warehouse' => 'Warehouse FG belum dikonfigurasi.']);
        }

        $normalizedLines = [];

        foreach ($validated['lines'] as $index => $lineData) {
            $itemId = (int) $lineData['item_id'];
            $qtyPacked = (float) $lineData['qty_packed'];

            if ($qtyPacked < 0) {
                $qtyPacked = 0;
            }

            if ($qtyPacked <= 0) {
                continue;
            }

            $fgBalance = (float) (
                InventoryStock::query()
                    ->where('warehouse_id', $fgWarehouseId)
                    ->where('item_id', $itemId)
                    ->value('qty') ?? 0
            );

            if ($qtyPacked > $fgBalance + 0.0001) {
                $item = Item::find($itemId);

                $itemLabel = $item
                ? trim(($item->code ?? '') . ' — ' . ($item->name ?? '') . ' ' . ($item->color ?? ''))
                : ('ID ' . $itemId);

                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.qty_packed" =>
                        'Qty Packing untuk item ' . $itemLabel .
                        ' melebihi saldo FG (' . $fgBalance . ').',
                    ]);
            }

            $normalizedLines[] = [
                'item_id' => $itemId,
                'qty_fg' => $fgBalance,
                'qty_packed' => $qtyPacked,
                'notes' => $lineData['notes'] ?? null,
            ];
        }

        if (empty($normalizedLines)) {
            return back()
                ->withInput()
                ->withErrors([
                    'lines' => 'Minimal harus ada 1 baris dengan Qty Packed > 0.',
                ]);
        }

        DB::transaction(function () use ($job, $validated, $normalizedLines, $request) {
            $job->update([
                'date' => $validated['date'],
                'channel' => $validated['channel'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            $job->lines()->delete();

            foreach ($normalizedLines as $line) {
                PackingJobLine::create([
                    'packing_job_id' => $job->id,
                    'item_id' => $line['item_id'],
                    'qty_fg' => $line['qty_fg'],
                    'qty_packed' => $line['qty_packed'],
                    'packed_at' => $validated['date'],
                    'notes' => $line['notes'],
                ]);
            }
        });

        return redirect()
            ->route('production.packing_jobs.show', $job->id)
            ->with('status', 'Packing Job berhasil diperbarui.');
    }

    /**
     * Posting: FG → PACKED.
     */
    public function post(PackingJob $packing_job): RedirectResponse
    {
        $job = $packing_job;

        if ($job->status === 'posted') {
            return redirect()
                ->route('production.packing_jobs.show', $job->id)
                ->with('status', 'Packing Job ini sudah diposting sebelumnya.');
        }

        // Pakai header, bukan hard-code
        $fgWarehouseId = $job->warehouse_from_id;
        $packedWarehouseId = $job->warehouse_to_id;

        if (!$fgWarehouseId || !$packedWarehouseId) {
            return back()->withErrors([
                'warehouse' => 'warehouse_from_id atau warehouse_to_id belum terisi pada Packing Job ini.',
            ]);
        }

        $date = $job->date instanceof \DateTimeInterface
        ? $job->date
        : Carbon::parse($job->date);

        $inventory = $this->inventory;

        try {
            DB::transaction(function () use ($job, $inventory, $fgWarehouseId, $packedWarehouseId, $date) {
                $job->load('lines');

                foreach ($job->lines as $line) {
                    $qty = (float) ($line->qty_packed ?? 0);

                    if ($qty <= 0) {
                        continue;
                    }

                    // Ambil unit cost di FG agar HPP ikut pindah ke PACKED
                    $unitCostFg = $inventory->getItemIncomingUnitCost(
                        warehouseId: $fgWarehouseId,
                        itemId: $line->item_id,
                    );

                    // 1) STOCK OUT dari FG
                    $inventory->stockOut(
                        warehouseId: $fgWarehouseId,
                        itemId: $line->item_id,
                        qty: $qty,
                        date: $date,
                        sourceType: PackingJob::class,
                        sourceId: $job->id,
                        notes: 'Packing ' . $job->code . ' (OUT FG)',
                        allowNegative: false,
                        lotId: null,
                        unitCost: $unitCostFg, // kalau stockOut kamu ignore unitCost, ini ga ngaruh
                    );

                    // 2) STOCK IN ke PACKED dengan unit cost FG
                    $inventory->stockIn(
                        warehouseId: $packedWarehouseId,
                        itemId: $line->item_id,
                        qty: $qty,
                        date: $date,
                        sourceType: PackingJob::class,
                        sourceId: $job->id,
                        notes: 'Packing ' . $job->code . ' (IN PACKED)',
                        lotId: null,
                        unitCost: $unitCostFg,
                    );
                }

                $job->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['inventory' => 'Gagal posting Packing Job: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('production.packing_jobs.show', $job->id)
            ->with('status', 'Packing Job berhasil diposting dan stok FG → PACKED sudah diperbarui.');
    }

    /**
     * Unpost: PACKED → FG.
     */
    public function unpost(PackingJob $packing_job): RedirectResponse
    {
        $job = $packing_job;

        if ($job->status !== 'posted') {
            return back()->with('status', 'Packing Job belum diposting, tidak bisa di-unpost.');
        }

        $fgWarehouseId = $job->warehouse_from_id;
        $packedWarehouseId = $job->warehouse_to_id;

        if (!$fgWarehouseId || !$packedWarehouseId) {
            return back()->with('status', 'warehouse_from_id atau warehouse_to_id belum terisi.');
        }

        $date = $job->date instanceof \DateTimeInterface
        ? $job->date
        : Carbon::parse($job->date);

        $inventory = $this->inventory;

        try {
            DB::transaction(function () use ($job, $inventory, $fgWarehouseId, $packedWarehouseId, $date) {
                $job->load('lines');

                foreach ($job->lines as $line) {
                    $qty = (float) ($line->qty_packed ?? 0);

                    if ($qty <= 0) {
                        continue;
                    }

                    // Ambil unit cost di PACKED, supaya nilai kembali ke FG
                    $unitCostPacked = $inventory->getItemIncomingUnitCost(
                        warehouseId: $packedWarehouseId,
                        itemId: $line->item_id,
                    );

                    // 1) STOCK OUT dari PACKED
                    $inventory->stockOut(
                        warehouseId: $packedWarehouseId,
                        itemId: $line->item_id,
                        qty: $qty,
                        date: $date,
                        sourceType: PackingJob::class,
                        sourceId: $job->id,
                        notes: 'Unpost Packing ' . $job->code . ' (OUT PACKED)',
                        allowNegative: false,
                        lotId: null,
                        unitCost: $unitCostPacked,
                    );

                    // 2) STOCK IN kembali ke FG
                    $inventory->stockIn(
                        warehouseId: $fgWarehouseId,
                        itemId: $line->item_id,
                        qty: $qty,
                        date: $date,
                        sourceType: PackingJob::class,
                        sourceId: $job->id,
                        notes: 'Unpost Packing ' . $job->code . ' (IN FG)',
                        lotId: null,
                        unitCost: $unitCostPacked,
                    );
                }

                $job->update([
                    'status' => 'draft',
                    'posted_at' => null,
                    'unposted_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('status', 'Gagal unpost Packing Job: ' . $e->getMessage());
        }

        return redirect()
            ->route('production.packing_jobs.show', $job->id)
            ->with('status', 'Packing Job berhasil di-unpost dan stok dikembalikan FG dari PACKED.');
    }
}
