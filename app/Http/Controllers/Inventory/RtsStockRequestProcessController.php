<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMutation;
use App\Models\StockRequest;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RtsStockRequestProcessController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Daftar permintaan RTS yang masuk ke PRD.
     */
    public function index(Request $request): View
    {
        // 🔹 filter dari query string
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
                    $dateFrom->copy()->startOfDay(),
                    $dateTo->copy()->endOfDay(),
                ]);
            }
            return $query;
        };

        // ========== BASE QUERY: hanya RTS Replenish ==========
        $baseQuery = StockRequest::rtsReplenish()
            ->with(['destinationWarehouse']) // RTS (yang minta)
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

        return view('inventory.prd_stock_requests.index', [
            'stockRequests' => $stockRequests,
            'stats' => $stats,
            'outstandingQty' => $outstandingQty,
            'statusFilter' => $statusFilter,
            'period' => $period,
        ]);
    }

    public function show(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse', 'requestedBy']);

        return view('inventory.prd_stock_requests.show', [
            'stockRequest' => $stockRequest,
        ]);
    }

    /**
     * Halaman proses 1 dokumen: PRD cek stok & isi qty_issued.
     */
    public function edit(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);

        $sourceWarehouseId = $stockRequest->source_warehouse_id;

        // live stock per line
        $liveStocks = [];
        foreach ($stockRequest->lines as $line) {
            $liveStocks[$line->id] = $this->inventory->getAvailableStock(
                $sourceWarehouseId,
                $line->item_id
            );
        }

        // default qty issued
        $defaultQtyIssued = [];
        foreach ($stockRequest->lines as $line) {
            $requested = (float) $line->qty_request;
            $available = (float) ($liveStocks[$line->id] ?? 0);

            $defaultQtyIssued[$line->id] = $requested > 0
            ? min($requested, $available)
            : 0;
        }

        // 🔹 histori movement
        $movementHistory = InventoryMutation::with(['item', 'warehouse'])
            ->where('source_type', 'stock_request')
            ->where('source_id', $stockRequest->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return view('inventory.prd_stock_requests.edit', [
            'stockRequest' => $stockRequest,
            'liveStocks' => $liveStocks,
            'defaultQtyIssued' => $defaultQtyIssued,
            'movementHistory' => $movementHistory,
        ]);
    }
    /**
     * Proses permintaan: gerakkan stok PRD -> RTS.
     */
    public function update(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load('lines');

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_issued' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $sourceWarehouseId = $stockRequest->source_warehouse_id;
        $destinationWarehouseId = $stockRequest->destination_warehouse_id;

        $linesInput = $validated['lines'];

        // Validasi stok live: qty_issued tidak boleh > stok sekarang
        $lineErrors = [];
        foreach ($stockRequest->lines as $line) {
            $input = $linesInput[$line->id] ?? [];
            $qtyIssued = isset($input['qty_issued'])
            ? (float) $input['qty_issued']
            : 0.0;

            if ($qtyIssued <= 0) {
                continue;
            }

            $available = $this->inventory->getAvailableStock($sourceWarehouseId, $line->item_id);

            if ($qtyIssued > $available) {
                $lineErrors["lines.{$line->id}.qty_issued"] =
                    "Qty kirim melebihi stok gudang produksi (stok saat ini: {$available}).";
            }
        }

        if (!empty($lineErrors)) {
            return back()
                ->withErrors($lineErrors)
                ->withInput();
        }

        DB::transaction(function () use (
            $stockRequest,
            $sourceWarehouseId,
            $destinationWarehouseId,
            $linesInput
        ) {
            $anyIssued = false;
            $allFullyIssued = true;

            foreach ($stockRequest->lines as $line) {
                $input = $linesInput[$line->id] ?? [];
                $qtyIssued = isset($input['qty_issued'])
                ? (float) $input['qty_issued']
                : 0.0;

                // Tidak mengirim apa-apa di baris ini
                if ($qtyIssued <= 0) {
                    $allFullyIssued = false;
                    continue;
                }

                $anyIssued = true;

                // Gerakkan stok: OUT PRD -> IN RTS
                $this->inventory->move(
                    $line->item_id,
                    $sourceWarehouseId,
                    $destinationWarehouseId,
                    $qtyIssued,
                    referenceType: 'stock_request',
                    referenceId: $stockRequest->id,
                    notes: 'RTS replenishment'
                );

                // Simpan qty_issued
                $line->qty_issued = $qtyIssued;
                $line->save();

                // Cek apakah fully issued
                if ($qtyIssued < (float) $line->qty_request) {
                    $allFullyIssued = false;
                }
            }

            // Update status header
            if (!$anyIssued) {
                // tidak ada yang dikirim, status tetap
                return;
            }

            if ($allFullyIssued) {
                $stockRequest->status = 'completed';
            } else {
                $stockRequest->status = 'partial';
            }

            $stockRequest->save();
        });

        return redirect()
            ->route('prd.stock-requests.index')
            ->with('status', 'Stock Request berhasil diproses. Stok sudah dipindahkan PRD → RTS.');
    }
}
