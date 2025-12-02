<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Services\Inventory\StockOpnameService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(
        protected StockOpnameService $stockOpnameService
    ) {
        // $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $warehouses = Warehouse::orderBy('code')->get();

        $query = StockOpname::with(['warehouse', 'creator'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        $opnames = $query->paginate(20);

        return view('inventory.stock_opnames.index', compact('opnames', 'warehouses'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::orderBy('code')->get();

        return view('inventory.stock_opnames.create', compact('warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'auto_generate_lines' => ['nullable', 'boolean'],
        ]);

        $autoGenerate = $request->boolean('auto_generate_lines', true);

        $opname = null;

        DB::transaction(function () use ($validated, $autoGenerate, &$opname) {
            // 1️⃣ header stock_opnames
            $opname = new StockOpname();
            $opname->code = $this->generateOpnameCode();
            $opname->warehouse_id = $validated['warehouse_id'];
            $opname->date = $validated['date'];
            $opname->notes = $validated['notes'] ?? null;
            $opname->status = 'counting'; // langsung status counting
            $opname->created_by = auth()->id();
            $opname->save();

            // 2️⃣ generate lines dari stok sistem
            if ($autoGenerate) {
                $this->stockOpnameService->generateLinesFromWarehouse(
                    opname: $opname,
                    warehouseId: $opname->warehouse_id,
                    onlyWithStock: true,
                );
            }
        });

        return redirect()
            ->route('inventory.stock_opnames.edit', $opname)
            ->with('status', 'success')
            ->with('message', 'Sesi stock opname berhasil dibuat.');
    }

    public function edit(StockOpname $stockOpname): View
    {
        $stockOpname->load(['warehouse', 'lines.item', 'creator']);

        return view('inventory.stock_opnames.edit', [
            'opname' => $stockOpname,
        ]);
    }

    public function show(StockOpname $stockOpname): View
    {
        $stockOpname->load(['warehouse', 'lines.item', 'creator', 'finalizer']);

        return view('inventory.stock_opnames.show', [
            'opname' => $stockOpname,
        ]);
    }

    /**
     * Helper generate kode SO-YYYYMMDD-###
     */
    private function generateOpnameCode(): string
    {
        $today = Carbon::today()->format('Ymd');
        $prefix = 'SO-' . $today . '-';

        // Cari code terakhir untuk hari ini
        $last = StockOpname::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        if ($last) {
            // ambil angka setelah prefix, misal SO-20251202-007 → 7
            $lastNumber = (int) substr($last->code, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s%03d', $prefix, $nextNumber);
    }

    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.physical_qty' => ['nullable', 'numeric'],
            'lines.*.system_qty' => ['nullable', 'numeric'],
            'lines.*.notes' => ['nullable', 'string'],
            'mark_reviewed' => ['nullable', 'boolean'],
        ]);

        $markReviewed = $request->boolean('mark_reviewed');

        DB::transaction(function () use ($stockOpname, $validated, $markReviewed, $request) {
            // 1️⃣ update header
            $stockOpname->notes = $validated['notes'] ?? $stockOpname->notes;

            // kalau masih draft → counting
            if ($stockOpname->status === 'draft') {
                $stockOpname->status = 'counting';
            }

            // kalau user klik "Simpan & Tandai Selesai Counting"
            if ($markReviewed) {
                $stockOpname->status = 'reviewed';
            }

            $stockOpname->save();

            // 2️⃣ update lines
            $linesInput = $validated['lines'] ?? [];

            if (!empty($linesInput)) {
                $stockOpname->load('lines');

                foreach ($linesInput as $lineId => $data) {
                    $line = $stockOpname->lines->firstWhere('id', (int) $lineId);
                    if (!$line) {
                        continue;
                    }

                    $systemQty = isset($data['system_qty'])
                    ? (float) $data['system_qty']
                    : (float) $line->system_qty;

                    $physicalQty = ($data['physical_qty'] ?? '') !== ''
                    ? (float) $data['physical_qty']
                    : null;

                    $difference = 0;
                    $isCounted = false;

                    if ($physicalQty !== null) {
                        $difference = $physicalQty - $systemQty;
                        $isCounted = true;
                    }

                    $line->system_qty = $systemQty;
                    $line->physical_qty = $physicalQty;
                    $line->difference_qty = $difference;
                    $line->is_counted = $isCounted;
                    $line->notes = $data['notes'] ?? $line->notes;
                    $line->save();
                }
            }
        });

        // 3️⃣ redirect tergantung tombol
        if ($markReviewed) {
            // Selesai counting → ke halaman review (show)
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'success')
                ->with('message', 'Counting selesai. Dokumen sudah ditandai sebagai reviewed.');
        }

        // Hanya simpan → tetap di halaman edit
        return redirect()
            ->route('inventory.stock_opnames.edit', $stockOpname)
            ->with('status', 'success')
            ->with('message', 'Perubahan hasil counting berhasil disimpan.');
    }

    public function finalize(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        // optional: hanya boleh finalize kalau status sudah reviewed
        if (!in_array($stockOpname->status, ['reviewed'])) {
            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Dokumen hanya bisa difinalkan jika status sudah reviewed.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $adjustment = $this->stockOpnameService->finalize(
                $stockOpname,
                $validated['reason'] ?? ('Penyesuaian stok dari stock opname ' . $stockOpname->code),
                $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('inventory.stock_opnames.show', $stockOpname)
                ->with('status', 'error')
                ->with('message', 'Gagal finalize stock opname: ' . $e->getMessage());
        }

        // Setelah finalize: redirect ke detail ADJ atau tetap ke detail opname
        return redirect()
            ->route('inventory.adjustments.show', $adjustment) // sesuaikan dengan nama route-mu
            ->with('status', 'success')
            ->with('message', 'Stock opname difinalkan. Adjustment: ' . $adjustment->code);
    }

}
