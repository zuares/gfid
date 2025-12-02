<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InventoryAdjustmentController extends Controller
{
    public function __construct()
    {
        // Tambah middleware kalau perlu
        // $this->middleware('auth');
    }

    /**
     * Daftar dokumen penyesuaian stok (Inventory Adjustment)
     */
    public function index(Request $request): View
    {
        $query = InventoryAdjustment::query()
            ->with(['warehouse', 'creator', 'approver'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        // Filter gudang
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        // Filter status
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $query->where('status', $status);
        }

        // Filter tanggal (opsional)
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        // Search code / reason
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('reason', 'like', '%' . $q . '%')
                    ->orWhere('notes', 'like', '%' . $q . '%');
            });
        }

        $adjustments = $query->paginate(25)->appends($request->query());

        $warehouses = Warehouse::orderBy('name')->get();

        $filters = [
            'warehouse_id' => $request->input('warehouse_id'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'q' => $request->input('q'),
        ];

        return view('inventory.inventory_adjustments.index', compact(
            'adjustments',
            'warehouses',
            'filters'
        ));
    }

    /**
     * Detail 1 dokumen adjustment
     */
    public function show(InventoryAdjustment $inventoryAdjustment): View
    {
        $inventoryAdjustment->load([
            'warehouse',
            'lines.item',
            'creator',
            'approver',
        ]);

        return view('inventory.inventory_adjustments.show', [
            'adjustment' => $inventoryAdjustment,
        ]);
    }
}
