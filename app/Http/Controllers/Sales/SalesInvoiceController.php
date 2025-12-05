<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Shipment;
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
        protected InventoryService $inventory, // disiapkan untuk integrasi stok/shipment
    ) {}

    /**
     * List invoice.
     */
    public function index()
    {
        $invoices = SalesInvoice::with(['customer', 'warehouse', 'store'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('sales.invoices.index', compact('invoices'));
    }

    /**
     * Form create invoice (manual, tidak dari shipment).
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('code')->get();
        $stores = Store::orderBy('code')->get();

        // Item FG + preload snapshot HPP aktif (final, dari ProductionCostPeriod aktif)
        $items = Item::query()
            ->where('type', 'finished_good')
            ->with('activeCostSnapshot') // relasi di model Item
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = $item->activeCostSnapshot?->unit_cost ?? 0.0;
                return $item;
            });

        return view('sales.invoices.create', [
            'customers' => $customers,
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,

            // penting untuk blade:
            'sourceShipment' => null,
            'defaultDate' => now()->toDateString(),
            'defaultWarehouseId' => null,
            'defaultCustomerId' => null,
            'defaultStoreId' => null,
            'prefilledLines' => [], // manual → kosong
        ]);
    }

    /**
     * Form create invoice dari Shipment (alur: Shipment → Invoice).
     */
    public function createFromShipment(Shipment $shipment)
    {
        // Load relasi yang dipakai di view (judul + info)
        $shipment->loadMissing([
            'lines.item',
            'store',
            'warehouse',
            // kalau nanti sudah ada relasi customer() di Shipment, bisa ditambah 'customer'
        ]);

        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('code')->get();
        $stores = Store::orderBy('code')->get();

        // Item FG (tetap dikirim supaya bisa tambah item manual)
        $items = Item::query()
            ->where('type', 'finished_good')
            ->with('activeCostSnapshot')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $item->hpp_unit = $item->activeCostSnapshot?->unit_cost ?? 0.0;
                return $item;
            });

        // Prefill lines dari shipment:
        // pakai qty_scanned kalau ada; fallback ke qty.
        $prefilledLines = $shipment->lines
            ->filter(fn($line) => $line->item_id && ($line->qty_scanned ?? $line->qty ?? 0) > 0)
            ->values()
            ->map(function ($line) {
                $qty = $line->qty_scanned ?? $line->qty ?? 0;

                return [
                    'item_id' => $line->item_id,
                    'qty' => $qty,
                    'unit_price' => null, // harga isi manual (atau nanti bisa auto dari price list)
                    'line_discount' => 0,
                ];
            })
            ->all();

        return view('sales.invoices.create', [
            'customers' => $customers,
            'warehouses' => $warehouses,
            'stores' => $stores,
            'items' => $items,

            // context shipment buat header & default value form
            'sourceShipment' => $shipment,
            'defaultDate' => optional($shipment->date)->toDateString() ?? now()->toDateString(),
            'defaultWarehouseId' => $shipment->warehouse_id ?? null,
            'defaultCustomerId' => $shipment->customer_id ?? null, // kalau kolom ini ada
            'defaultStoreId' => $shipment->store_id ?? null,
            'prefilledLines' => $prefilledLines,
        ]);
    }

    /**
     * Simpan invoice + line + hitung HPP & margin.
     */
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

            // kalau datang dari Shipment
            'source_shipment_id' => ['nullable', 'exists:shipments,id'],

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
        $sourceShipmentId = $data['source_shipment_id'] ?? null;

        // Sederhana dulu, nanti bisa pakai generator terpisah
        $code = 'INV-' . now()->format('Ymd') . '-' . str_pad(
            SalesInvoice::count() + 1,
            3,
            '0',
            STR_PAD_LEFT
        );

        /** @var \App\Models\SalesInvoice $invoice */
        $invoice = DB::transaction(function () use (
            $data,
            $taxPercent,
            $headerDiscount,
            $invoiceDate,
            $warehouseId,
            $code,
            $sourceShipmentId
        ) {
            // 1️⃣ Buat header invoice (status draft)
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

            // 2️⃣ Detail line + HPP + margin
            $subtotal = 0.0;

            foreach ($data['items'] as $row) {
                $itemId = (int) $row['item_id'];
                $qty = (int) $row['qty'];
                $unitPrice = (float) $row['unit_price'];
                $lineDiscount = (float) ($row['line_discount'] ?? 0.0);

                $lineTotal = max(0, ($qty * $unitPrice) - $lineDiscount);
                $subtotal += $lineTotal;

                // 🔥 Ambil HPP FINAL aktif (ProductionCostPeriod aktif) via HppService
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

            // 3️⃣ Hitung ringkasan header (subtotal, diskon, pajak, grand total)
            $discountTotal = min($headerDiscount, $subtotal);
            $dpp = $subtotal - $discountTotal;

            $taxAmount = $taxPercent > 0 ? round($dpp * $taxPercent / 100, 2) : 0.0;
            $grandTotal = $dpp + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);

            // 4️⃣ Kalau invoice ini berasal dari Shipment tertentu → link-kan
            if ($sourceShipmentId) {
                Shipment::where('id', $sourceShipmentId)
                    ->whereNull('sales_invoice_id') // jangan overwrite kalau sudah ada
                    ->update([
                        'sales_invoice_id' => $invoice->id,
                    ]);
            }

            return $invoice;
        });

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->code} berhasil dibuat.");
    }

    /**
     * Detail invoice (+ relasi shipment).
     */
    public function show(SalesInvoice $invoice)
    {
        $invoice->load([
            'customer',
            'warehouse',
            'store',
            'lines.item',
            'shipments', // relasi ke Shipment (hasMany atau hasOneThrough sesuai desainmu)
        ]);

        return view('sales.invoices.show', compact('invoice'));
    }

    /**
     * Posting invoice → hanya lock status.
     * Stok akan berkurang saat Shipment di-post dari WH-RTS.
     */
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
            "Invoice {$invoice->code} berhasil diposting. Stok akan berkurang saat Shipment diposting dari WH-RTS."
        );
    }
}
