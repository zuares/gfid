<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StockRequestLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RtsStockRequestController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Daftar Stock Request RTS (permintaan dari RTS ke PRD).
     */
    public function index(Request $request): View
    {
        // 🔹 default status = all supaya completed juga langsung kelihatan
        $statusFilter = $request->input('status', 'all'); // pending | submitted | partial | completed | all
        $period = $request->input('period', 'today'); // today | week | month | all

        // ========== HITUNG RANGE TANGGAL BERDASARKAN PERIOD ==========
        $dateFrom = null;
        $dateTo = null;

        switch ($period) {
            case 'week':
                $dateFrom = Carbon::now()->startOfWeek(); // Senin
                $dateTo = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $dateFrom = Carbon::now()->startOfMonth();
                $dateTo = Carbon::now()->endOfMonth();
                break;

            case 'all':
                // tanpa batas tanggal
                break;

            case 'today':
            default:
                $dateFrom = Carbon::today();
                $dateTo = Carbon::today();
                $period = 'today';
                break;
        }

        // Helper closure untuk apply filter tanggal ke query manapun
        $applyDateFilter = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom && $dateTo) {
                $query->whereBetween('date', [
                    $dateFrom->copy()->startOfDay(), // 00:00:00
                    $dateTo->copy()->endOfDay(), // 23:59:59
                ]);
            }
            return $query;
        };

        // ========== BASE QUERY: hanya RTS Replenish ==========
        $baseQuery = StockRequest::rtsReplenish()
            ->with(['sourceWarehouse', 'destinationWarehouse'])
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_issued_qty', 'qty_issued');

        $baseQuery = $applyDateFilter($baseQuery);

        // ========== DASHBOARD STATS (ikut period juga) ==========
        $statsBase = StockRequest::rtsReplenish();
        $statsBase = $applyDateFilter($statsBase);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'submitted' => (clone $statsBase)->where('status', 'submitted')->count(),
            'partial' => (clone $statsBase)->where('status', 'partial')->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
        ];
        $stats['pending'] = $stats['submitted'] + $stats['partial'];

        // Outstanding qty (request - issued) untuk semua dokumen dalam period
        $outstandingQty = (clone $statsBase)
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_issued_qty', 'qty_issued')
            ->get()
            ->sum(function ($req) {
                $reqQty = (float) ($req->total_requested_qty ?? 0);
                $issuedQty = (float) ($req->total_issued_qty ?? 0);
                return max($reqQty - $issuedQty, 0);
            });

        // ========== FILTER LIST VIEW (status + period) ==========
        $listQuery = clone $baseQuery;

        switch ($statusFilter) {
            case 'submitted':
                $listQuery->where('status', 'submitted');
                break;

            case 'partial':
                $listQuery->where('status', 'partial');
                break;

            case 'completed':
                $listQuery->where('status', 'completed');
                break;

            case 'pending':
                // Pending = submitted + partial
                $listQuery->whereIn('status', ['submitted', 'partial']);
                break;

            case 'all':
            default:
                // tidak filter status
                $statusFilter = 'all';
                break;
        }

        $stockRequests = $listQuery
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.rts_stock_requests.index', [
            'stockRequests' => $stockRequests,
            'stats' => $stats,
            'outstandingQty' => $outstandingQty,
            'statusFilter' => $statusFilter,
            'period' => $period,
        ]);
    }

    /**
     * Form buat Stock Request dari RTS ke PRD.
     */
    public function create(): View
    {
        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->firstOrFail();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        // Item yang saldo stok > 0 di WH-PRD
        $finishedGoodsItems = Item::whereHas('inventoryStocks', function ($q) use ($prdWarehouse) {
            $q->where('warehouse_id', $prdWarehouse->id)
                ->where('qty', '>', 0); // atau qty_available
        })
            ->orderBy('name')
            ->get();

        return view('inventory.rts_stock_requests.create', [
            'prdWarehouse' => $prdWarehouse,
            'rtsWarehouse' => $rtsWarehouse,
            'finishedGoodsItems' => $finishedGoodsItems,
        ]);
    }

    /**
     * Simpan Stock Request dari RTS.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'source_warehouse_id' => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_request' => ['required', 'numeric', 'gt:0'],
        ], [
            'lines.required' => 'Minimal harus ada 1 baris item.',
            'lines.*.item_id.required' => 'Pilih item untuk setiap baris.',
            'lines.*.qty_request.required' => 'Isi qty untuk setiap baris.',
        ]);

        $sourceWarehouseId = (int) $validated['source_warehouse_id'];

        // Validasi stok di backend per item
        $lineErrors = [];
        foreach ($validated['lines'] as $index => $line) {
            $itemId = (int) $line['item_id'];
            $qtyRequest = (float) $line['qty_request'];

            $available = $this->inventory->getAvailableStock($sourceWarehouseId, $itemId);

            if ($qtyRequest > $available) {
                $lineErrors["lines.$index.qty_request"] =
                    "Qty melebihi stok di gudang asal (stok saat ini: {$available}).";
            }
        }

        if (!empty($lineErrors)) {
            return back()
                ->withErrors($lineErrors)
                ->withInput();
        }

        // Generate kode dokumen sederhana: SR-YYYYMMDD-###
        $date = Carbon::parse($validated['date']);
        $code = $this->generateCodeForDate($date);

        DB::transaction(function () use ($validated, $date, $code, $sourceWarehouseId) {
            $stockRequest = StockRequest::create([
                'code' => $code,
                'date' => $date,
                'purpose' => 'rts_replenish',
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'status' => 'submitted', // langsung submit ke PRD
                'requested_by_user_id' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['lines'] as $i => $lineData) {
                $itemId = (int) $lineData['item_id'];
                $qtyRequest = (float) $lineData['qty_request'];

                $available = $this->inventory->getAvailableStock($sourceWarehouseId, $itemId);

                StockRequestLine::create([
                    'stock_request_id' => $stockRequest->id,
                    'line_no' => $i + 1,
                    'item_id' => $itemId,
                    'qty_request' => $qtyRequest,
                    'stock_snapshot_at_request' => $available,
                    'qty_issued' => null,
                    'notes' => $lineData['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('rts.stock-requests.index')
            ->with('status', 'Stock Request berhasil dibuat dan dikirim ke Gudang Produksi.');
    }

    /**
     * Tampilkan detail Stock Request.
     */
    public function show(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load([
            'lines.item',
            'sourceWarehouse',
            'destinationWarehouse',
            'requestedBy',
        ]);

        // 🔧 FIX: jangan compact('request') — variabelnya adalah $stockRequest
        return view('inventory.rts_stock_requests.show', [
            'stockRequest' => $stockRequest,
        ]);
    }

    /**
     * Generate kode dokumen: SR-YYYYMMDD-###.
     */
    protected function generateCodeForDate(Carbon $date): string
    {
        $prefix = 'SR-' . $date->format('Ymd');

        $last = StockRequest::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        $nextNumber = 1;

        if ($last) {
            $lastNumber = (int) substr($last->code, -3);
            $nextNumber = $lastNumber + 1;
        }

        return sprintf('%s-%03d', $prefix, $nextNumber);
    }
}
