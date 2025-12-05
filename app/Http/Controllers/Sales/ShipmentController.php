<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    public function index()
    {
        $shipments = Shipment::with(['customer', 'warehouse', 'invoice'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('sales.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['customer', 'warehouse', 'invoice', 'lines.item']);

        return view('sales.shipments.show', compact('shipment'));
    }

    /**
     * Form generic create shipment (tanpa invoice).
     * Kalau kamu lebih fokus dari Invoice dulu, ini bisa di-skip sementara.
     */
    public function create()
    {
        return view('sales.shipments.create', [
            'customers' => Customer::orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('code')->get(),
            'items' => Item::where('type', 'finished_good')->orderBy('code')->get(),
        ]);
    }

    /**
     * Form create Shipment dari Sales Invoice.
     */
    public function createFromInvoice(SalesInvoice $invoice)
    {
        $invoice->load('lines.item', 'customer', 'warehouse');

        return view('sales.shipments.create_from_invoice', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Simpan Shipment (baik dari invoice maupun generic).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'sales_invoice_id' => 'nullable|exists:sales_invoices,id',
            'customer_id' => 'nullable|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'shipping_method' => 'nullable|string|max:100',
            'tracking_no' => 'nullable|string|max:100',
            'shipping_address' => 'nullable|string',
            'remarks' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.sales_invoice_line_id' => 'nullable|exists:sales_invoice_lines,id',
        ]);

        return DB::transaction(function () use ($data) {
            $code = 'SHP-' . now()->format('Ymd') . '-' . str_pad(
                Shipment::count() + 1,
                3,
                '0',
                STR_PAD_LEFT
            );

            $shipment = Shipment::create([
                'code' => $code,
                'date' => $data['date'],
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'status' => 'draft', // nanti bisa diubah ke 'shipped'
                'shipping_method' => $data['shipping_method'] ?? null,
                'tracking_no' => $data['tracking_no'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $row) {
                ShipmentLine::create([
                    'shipment_id' => $shipment->id,
                    'sales_invoice_line_id' => $row['sales_invoice_line_id'] ?? null,
                    'item_id' => $row['item_id'],
                    'qty' => $row['qty'],
                    'scan_code' => $row['scan_code'] ?? null,
                ]);
            }

            return redirect()
                ->route('sales.shipments.show', $shipment)
                ->with('success', "Shipment {$shipment->code} berhasil dibuat.");
        });
    }

    public function ship(Shipment $shipment)
    {

        if ($shipment->status === 'shipped') {
            return back()->with('info', "Shipment {$shipment->code} sudah berstatus shipped.");
        }

        // pastikan relasi yang dibutuhkan sudah diload
        $shipment->load(['lines', 'warehouse', 'invoice']);

        // tentukan gudang sumber: pakai warehouse shipment, kalau null pakai warehouse invoice
        $warehouseId = $shipment->warehouse_id ?? $shipment->invoice?->warehouse_id;

        if (!$warehouseId) {
            return back()->with('error', 'Warehouse untuk shipment ini tidak jelas (tidak diisi di shipment maupun invoice).');
        }

        if ($shipment->lines->isEmpty()) {
            return back()->with('error', 'Shipment tidak memiliki item, tidak bisa di-ship.');
        }

        try {
            DB::transaction(function () use ($shipment, $warehouseId) {

                foreach ($shipment->lines as $line) {
                    $qty = (float) $line->qty;
                    if ($qty <= 0) {
                        continue; // skip baris qty 0
                    }

                    $this->inventory->stockOut(
                        warehouseId: $warehouseId,
                        itemId: $line->item_id,
                        qty: $qty,
                        date: $shipment->date,
                        sourceType: 'shipment',
                        sourceId: $shipment->id,
                        notes: "Shipment {$shipment->code}",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false, // FG, jangan ganggu LotCost kain
                    );
                }

                $shipment->update([
                    'status' => 'shipped',
                ]);
            });
        } catch (\RuntimeException $e) {
            // biasanya dari stockOut: stok tidak mencukupi
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal ship: ' . $e->getMessage());
        }

        return back()->with('success', "Shipment {$shipment->code} berhasil dikirim & stok berkurang.");
    }

}
