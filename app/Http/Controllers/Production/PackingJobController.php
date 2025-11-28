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
use App\Services\Production\PackingStatusService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PackingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory // saat ini tidak dipakai, gapapa buat future
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
            ->with(['createdBy', 'warehouseFrom', 'warehouseTo'])
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
     * List item dengan stok di WH-PRD (opsional, untuk browsing).
     */
    public function readyItems(Request $request, PackingStatusService $packingStatus): View
    {
        // Sumber packing: Gudang Produksi (WH-PRD)
        $prodWarehouse = Warehouse::query()
            ->where('code', 'WH-PRD')
            ->first();

        $stocks = collect();
        $packedQtyByItem = [];

        if ($prodWarehouse) {
            $query = InventoryStock::query()
                ->with('item')
                ->where('warehouse_id', $prodWarehouse->id)
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

            // Hitung total packed untuk semua item di halaman ini (supaya nggak N+1)
            $itemIds = $stocks->pluck('item_id')->filter()->unique()->values()->all();

            $packedQtyByItem = $packingStatus->getPackedQtyMapForItems(
                warehouseId: $prodWarehouse->id,
                itemIds: $itemIds,
                statuses: ['draft', 'posted'], // kalau mau hanya posted, ganti ['posted']
            );
        }

        return view('production.packing_jobs.ready_items', [
            'stocks' => $stocks,
            'prodWarehouse' => $prodWarehouse,
            'packedQtyByItem' => $packedQtyByItem,
        ]);
    }

    /**
     * Form create Packing Job.
     * PURE STATUS: hanya catat qty_packed, tidak mengubah stok.
     */
    public function create(Request $request): View
    {
        $date = Carbon::today()->toDateString();

        // Sumber stok: gudang produksi WH-PRD (hasil finishing)
        $prodWarehouseId = Warehouse::where('code', 'WH-PRD')->value('id');

        $stocksQuery = InventoryStock::query()
            ->with('item')
            ->when($prodWarehouseId, function ($q) use ($prodWarehouseId) {
                $q->where('warehouse_id', $prodWarehouseId)
                    ->where('qty', '>', 0.0001);
            })
            ->orderBy('item_id');

        $stocks = $stocksQuery->get();

        // item_ids[] dari halaman ready_items (kalau user pilih dari sana)
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

                $prdBalance = (float) $stock->qty;

                $lines[] = [
                    'item_id' => $stock->item_id,
                    'item_label' => $label,
                    'fg_balance' => $prdBalance, // tetap pakai key fg_balance biar cocok dengan Blade
                    'qty_packed' => $prdBalance, // default: pack semua, boleh diubah user
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
     * PURE STATUS: tidak mengubah inventory, hanya status.
     */
    public function store(Request $request, PackingStatusService $packingStatus): RedirectResponse
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

        // 2. Pastikan gudang produksi WH-PRD ada
        $prodWarehouseId = Warehouse::where('code', 'WH-PRD')->value('id');

        if (!$prodWarehouseId) {
            return back()
                ->withInput()
                ->withErrors([
                    'warehouse' => 'Warehouse WH-PRD (gudang produksi) belum dikonfigurasi. Atur dulu di master gudang.',
                ]);
        }

        $normalizedLines = [];

        // 3. Normalisasi & validasi per baris (stock + available_for_packing)
        foreach ($validated['lines'] as $index => $lineData) {
            $itemId = (int) $lineData['item_id'];
            $qtyPacked = (float) $lineData['qty_packed'];

            if ($qtyPacked < 0) {
                $qtyPacked = 0;
            }

            // Abaikan baris dengan 0
            if ($qtyPacked <= 0) {
                continue;
            }

            // a) Stok produksi saat ini di WH-PRD
            $prdBalance = (float) (
                InventoryStock::query()
                    ->where('warehouse_id', $prodWarehouseId)
                    ->where('item_id', $itemId)
                    ->value('qty') ?? 0
            );

            // b) Total yang sudah pernah di-pack oleh job lain (draft+posted)
            $alreadyPacked = $packingStatus->getPackedQtyForItem(
                warehouseId: $prodWarehouseId,
                itemId: $itemId,
                statuses: ['draft', 'posted'],
            );

            // Karena ini job baru, tidak ada self quantity yang perlu dikoreksi.
            $available = max(0, $prdBalance - $alreadyPacked);

            if ($qtyPacked > $available + 0.0001) {
                $item = Item::find($itemId);

                $itemLabel = $item
                ? trim(($item->code ?? '') . ' — ' . ($item->name ?? '') . ' ' . ($item->color ?? ''))
                : ('ID ' . $itemId);

                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.qty_packed" =>
                        'Qty Packed untuk item ' . $itemLabel . ' melebihi stok available di WH-PRD. ' .
                        'Stok: ' . $prdBalance . ', sudah dipacking: ' . $alreadyPacked .
                        ', sisa available: ' . $available . '.',
                    ]);
            }

            $normalizedLines[] = [
                'item_id' => $itemId,
                'qty_fg' => $prdBalance, // referensi saldo saat ini (boleh kamu rename nanti)
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
        $job = DB::transaction(function () use ($validated, $normalizedLines, $request, $prodWarehouseId) {
            $code = CodeGenerator::generate('PCK'); // prefix bebas sesuai gaya kamu

            /** @var \App\Models\PackingJob $job */
            $job = PackingJob::create([
                'code' => $code,
                'date' => $validated['date'],
                'status' => 'draft',
                'channel' => $validated['channel'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'warehouse_from_id' => $prodWarehouseId, // sumber WH-PRD
                'warehouse_to_id' => $prodWarehouseId, // pure status → gudang sama
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($normalizedLines as $line) {
                PackingJobLine::create([
                    'packing_job_id' => $job->id,
                    'item_id' => $line['item_id'],
                    'qty_fg' => $line['qty_fg'], // snapshot stok saat itu
                    'qty_packed' => $line['qty_packed'],
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

        // Tetap ambil stok dari WH-PRD
        $prodWarehouseId = Warehouse::where('code', 'WH-PRD')->value('id');

        $stocks = InventoryStock::query()
            ->with('item')
            ->when($prodWarehouseId, function ($q) use ($prodWarehouseId) {
                $q->where('warehouse_id', $prodWarehouseId)
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
                'fg_balance' => $line->qty_fg, // di job lama kita simpan refer saldo di sini
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
     * PURE STATUS.
     */
    public function update(
        Request $request,
        PackingJob $packing_job,
        PackingStatusService $packingStatus
    ): RedirectResponse {
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

        $prodWarehouseId = $job->warehouse_from_id ?: Warehouse::where('code', 'WH-PRD')->value('id');

        if (!$prodWarehouseId) {
            return back()
                ->withInput()
                ->withErrors(['warehouse' => 'Warehouse WH-PRD belum dikonfigurasi.']);
        }

        // Hitung total qty_packed dari job ini per item (untuk koreksi "self")
        $currentJobPackedByItem = $job->lines()
            ->selectRaw('item_id, SUM(qty_packed) as total_packed')
            ->groupBy('item_id')
            ->pluck('total_packed', 'item_id')
            ->map(fn($v) => (float) $v)
            ->toArray();

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

            // a) Stok produksi saat ini
            $prdBalance = (float) (
                InventoryStock::query()
                    ->where('warehouse_id', $prodWarehouseId)
                    ->where('item_id', $itemId)
                    ->value('qty') ?? 0
            );

            // b) Total yang sudah di-pack (semua job) untuk item ini
            $alreadyPackedGlobal = $packingStatus->getPackedQtyForItem(
                warehouseId: $prodWarehouseId,
                itemId: $itemId,
                statuses: ['draft', 'posted'],
            );

            // c) Self quantity dari job ini (sebelum update)
            $selfPacked = $currentJobPackedByItem[$itemId] ?? 0.0;

            // d) Effective already (job lain saja)
            $effectiveAlready = max(0, $alreadyPackedGlobal - $selfPacked);

            // e) Available setelah kurangi job lain
            $available = max(0, $prdBalance - $effectiveAlready);

            if ($qtyPacked > $available + 0.0001) {
                $item = Item::find($itemId);

                $itemLabel = $item
                ? trim(($item->code ?? '') . ' — ' . ($item->name ?? '') . ' ' . ($item->color ?? ''))
                : ('ID ' . $itemId);

                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.qty_packed" =>
                        'Qty Packed untuk item ' . $itemLabel . ' melebihi stok available di WH-PRD. ' .
                        'Stok: ' . $prdBalance . ', sudah dipacking (job lain): ' . $effectiveAlready .
                        ', sisa available: ' . $available . '.',
                    ]);
            }

            $normalizedLines[] = [
                'item_id' => $itemId,
                'qty_fg' => $prdBalance,
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
            // Update header
            $job->update([
                'date' => $validated['date'],
                'channel' => $validated['channel'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            // Hapus semua line lama, insert ulang (karena masih draft aman)
            $job->lines()->delete();

            foreach ($normalizedLines as $line) {
                PackingJobLine::create([
                    'packing_job_id' => $job->id,
                    'item_id' => $line['item_id'],
                    'qty_fg' => $line['qty_fg'],
                    'qty_packed' => $line['qty_packed'],
                    'notes' => $line['notes'],
                ]);
            }
        });

        return redirect()
            ->route('production.packing_jobs.show', $job->id)
            ->with('status', 'Packing Job berhasil diperbarui.');
    }

    /**
     * Posting: hanya ubah status draft → posted (PURE STATUS).
     */
    public function post(PackingJob $packing_job): RedirectResponse
    {
        $job = $packing_job->load('lines');

        if ($job->status === 'posted') {
            return redirect()
                ->route('production.packing_jobs.show', $job->id)
                ->with('status', 'Packing Job ini sudah diposting sebelumnya (status saja).');
        }

        // Optional: pastikan ada minimal 1 baris dengan qty_packed > 0
        $totalPacked = (float) $job->lines->sum('qty_packed');

        if ($totalPacked <= 0) {
            return back()
                ->withErrors([
                    'lines' => 'Tidak bisa posting Packing Job tanpa Qty Packed. Minimal satu baris harus punya Qty Packed > 0.',
                ]);
        }

        $job->update([
            'status' => 'posted',
            'posted_at' => now(),
            'unposted_at' => null,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('production.packing_jobs.show', $job->id)
            ->with('status', 'Packing Job berhasil diposting sebagai status (tanpa mengubah stok WH-PRD).');
    }

    /**
     * Unpost: posted → draft (PURE STATUS).
     */
    public function unpost(PackingJob $packing_job): RedirectResponse
    {
        $job = $packing_job;

        if ($job->status !== 'posted') {
            return back()->with('status', 'Packing Job belum berstatus posted, tidak bisa di-unpost.');
        }

        $job->update([
            'status' => 'draft',
            'posted_at' => null,
            'unposted_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('production.packing_jobs.show', $job->id)
            ->with('status', 'Packing Job berhasil di-unpost. Perubahan ini hanya status, tidak mengubah stok.');
    }

}
