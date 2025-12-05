<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Costing\HppService;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function __construct(
        protected HppService $hpp,
        protected InventoryService $inventory, // masih disiapkan kalau nanti mau dipakai shipment/cek stok
    ) {}

    public function index()
    {
        $invoices = SalesInvoice::with(['customer', 'warehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('sales.invoices.index', compact('invoices'));
    }

    public function create()
    {
        // Master data dulu, biar rapi
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('code')->get();
        $stores = Store::orderBy('code')->get();

        // Item FG + eager load snapshot HPP aktif (dari ProductionCostPeriod)
        $items = Item::where('type', 'finished_good')
            ->with('activeCostSnapshot') // pastikan relasi ini ada di model Item
            ->orderBy('code')
            ->get()
            ->map(function ($i) {
                // null-safe operator: kalau nggak ada snapshot aktif → 0
                $i->hpp_unit = $i->activeCostSnapshot?->unit_cost ?? 0;

                return $i;
            });

        return view('sales.invoices.create', [
            'customers' => $customers,
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'remarks' => ['nullable', 'string'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'header_discount' => ['nullable', 'numeric', 'min:0'],

            'store_id' => ['nullable', 'exists:stores,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:1'],
            'items.*.line_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $taxPercent = (float) ($data['tax_percent'] ?? 0);
        $headerDiscount = (float) ($data['header_discount'] ?? 0);

        $invoiceDate = $data['date'];
        $warehouseId = (int) $data['warehouse_id']; // biasanya WH-RTS

        // Sederhana dulu, nanti bisa diganti generator yang lebih proper
        $code = 'INV-' . now()->format('Ymd') . '-' . str_pad(SalesInvoice::count() + 1, 3, '0', STR_PAD_LEFT);

        $invoice = SalesInvoice::create([
            'code' => $code,
            'date' => $invoiceDate,
            'customer_id' => $data['customer_id'] ?? null,
            'store_id' => $data['store_id'] ?? null,
            'warehouse_id' => $warehouseId,
            'status' => 'draft',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => auth()->id(),
            'tax_percent' => $taxPercent,
        ]);

        $subtotal = 0.0;

        foreach ($data['items'] as $line) {
            $itemId = (int) $line['item_id'];
            $qty = (int) $line['qty'];
            $unitPrice = (float) $line['unit_price'];
            $lineDiscount = (float) ($line['line_discount'] ?? 0.0);

            $lineTotal = max(0, ($qty * $unitPrice) - $lineDiscount);
            $subtotal += $lineTotal;

            // ✅ HPP per unit dari HppService → HPP FINAL (ProductionCostPeriod aktif)
            $hppSnapshot = $this->hpp->getActiveFinalHppForItem($itemId, $warehouseId);
            $hppUnit = $hppSnapshot?->unit_cost ?? 0.0;

            // Hitung margin berbasis HPP final
            $costTotal = $hppUnit * $qty;
            $marginTotal = $lineTotal - $costTotal;
            $marginUnit = $qty > 0 ? $marginTotal / $qty : 0.0;

            SalesInvoiceLine::create([
                'sales_invoice_id' => $invoice->id,
                'item_id' => $itemId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_discount' => $lineDiscount,
                'line_total' => $lineTotal,
                'hpp_unit_snapshot' => $hppUnit,
                'margin_unit' => $marginUnit,
                'margin_total' => $marginTotal,
            ]);
        }

        // Header summary
        $discountTotal = min($headerDiscount, $subtotal);
        $dpp = $subtotal - $discountTotal;

        $taxAmount = $taxPercent > 0 ? round($dpp * $taxPercent / 100, 2) : 0;
        $grandTotal = $dpp + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
        ]);

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->code} berhasil dibuat.");
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load([
            'customer',
            'warehouse',
            'lines.item',
            'shipments', // relasi baru
        ]);

        return view('sales.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    public function post(SalesInvoice $invoice)
    {
        if ($invoice->status === 'posted') {
            return back()->with('info', "Invoice {$invoice->code} sudah berstatus posted.");
        }

        $invoice->load('lines');
        if ($invoice->lines->isEmpty()) {
            return back()->with('error', 'Invoice tidak memiliki item, tidak bisa diposting.');
        }

        try {
            DB::transaction(function () use ($invoice) {
                $invoice->update([
                    'status' => 'posted',
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memposting invoice: ' . $e->getMessage());
        }

        return back()->with(
            'success',
            "Invoice {$invoice->code} berhasil diposting. Stok akan berkurang saat Shipment di-mark shipped."
        );
    }
}
