<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StockApiController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Cek stok available per gudang + item.
     * GET /api/stock/available?warehouse_id=&item_id=
     */
    public function available(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'item_id' => ['required', 'exists:items,id'],
        ]);

        $warehouseId = (int) $validated['warehouse_id'];
        $itemId = (int) $validated['item_id'];

        $available = $this->inventory->getAvailableStock($warehouseId, $itemId);

        return response()->json([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'available' => $available,
            // Kalau suatu saat kamu punya on_hand & reserved, bisa tambah di sini.
        ]);
    }

    /**
     * Ringkasan stok per gudang untuk 1 item (popup 🔍).
     * GET /api/stock/summary?item_id=
     */
    public function summary(Request $request)
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
        ]);

        $itemId = (int) $validated['item_id'];

        $item = Item::findOrFail($itemId);
        $summary = $this->inventory->getStockSummaryForItem($itemId);

        // summary sebaiknya format:
        // [
        //   [
        //     'warehouse_id' => 2,
        //     'code' => 'WH-PRD',
        //     'name' => 'Gudang Produksi',
        //     'on_hand' => 150,
        //     'reserved' => 30,
        //     'available' => 120,
        //   ],
        //   ...
        // ]

        return response()->json([
            'item' => [
                'id' => $item->id,
                'code' => $item->code ?? null,
                'name' => $item->name,
            ],
            'warehouses' => $summary,
        ], Response::HTTP_OK);
    }
}
