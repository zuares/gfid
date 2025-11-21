<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\SupplierPrice;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Create Purchase Order baru + detail lines.
     *
     * $payload contoh:
     * [
     *   'date'          => '2025-11-21',
     *   'supplier_id'   => 1,
     *   'discount'      => 0,          // diskon header (nominal)
     *   'tax_percent'   => 11,         // PPN dalam %
     *   'shipping_cost' => 25000,
     *   'notes'         => 'Catatan PO',
     *   'created_by'    => 1,
     *   'lines' => [
     *      [
     *          'item_id'    => 1,
     *          'qty'        => 100,
     *          'unit_price' => 12000,
     *          'discount'   => 0,      // diskon per baris (nominal)
     *          'notes'      => 'Keterangan',
     *      ],
     *      // ...
     *   ],
     * ]
     */
    public function create(array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            // Generate kode jika belum diisi
            if (empty($payload['code'] ?? null)) {
                $payload['code'] = CodeGenerator::generate('PO');
            }

            // Set default angka
            $payload['subtotal'] = 0;
            $payload['discount'] = $this->toNumber($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->toNumber($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->toNumber($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;

            // Status default
            $payload['status'] = $payload['status'] ?? 'draft';

            /** @var PurchaseOrder $order */
            $order = PurchaseOrder::create($payload);

            // Simpan detail + hitung subtotal
            $subtotal = $this->syncLines($order, $linesData);

            // Hitung total header
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines', 'supplier']);
        });
    }

    /**
     * Update Purchase Order + detail lines.
     *
     * $payload struktur sama dengan create()
     */
    public function update(PurchaseOrder $order, array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            // Kalau code dikosongkan, biarkan kode lama (tidak diubah)
            unset($payload['code']);

            // Update field header yang boleh diubah
            if (array_key_exists('date', $payload)) {
                $order->date = $payload['date'];
            }
            if (array_key_exists('supplier_id', $payload)) {
                $order->supplier_id = $payload['supplier_id'];
            }
            if (array_key_exists('discount', $payload)) {
                $order->discount = $this->toNumber($payload['discount']);
            }
            if (array_key_exists('tax_percent', $payload)) {
                $order->tax_percent = $this->toNumber($payload['tax_percent']);
            }
            if (array_key_exists('shipping_cost', $payload)) {
                $order->shipping_cost = $this->toNumber($payload['shipping_cost']);
            }
            if (array_key_exists('notes', $payload)) {
                $order->notes = $payload['notes'];
            }
            if (array_key_exists('status', $payload)) {
                $order->status = $payload['status'];
            }

            $order->save();

            // Sync detail & hitung subtotal
            $subtotal = $this->syncLines($order, $linesData);

            // Hitung ulang total header
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines', 'supplier']);
        });
    }

    /**
     * Force hitung ulang subtotal, tax, grand_total dari database.
     * Bisa dipakai kalau suatu saat ada perubahan di lines langsung.
     */
    public function recalculate(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $subtotal = $order->lines()->sum('line_total');

            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines', 'supplier']);
        });
    }

    // ======================================================================
    // HELPER INTERNAL
    // ======================================================================

    /**
     * Simpan ulang detail lines.
     * Saat ini implementasi sederhana: hapus semua lalu insert ulang.
     * Nanti kalau mau lebih advanced bisa di-update per-line (by id).
     *
     * @param  PurchaseOrder $order
     * @param  array $linesData
     * @return float subtotal
     */
    protected function syncLines(PurchaseOrder $order, array $linesData): float
    {
        // Hapus semua detail lama
        $order->lines()->delete();

        $subtotal = 0.0;

        foreach ($linesData as $row) {
            $itemId = $row['item_id'] ?? null;
            $qty = $this->toNumber($row['qty'] ?? 0);
            $unitPrice = $this->toNumber($row['unit_price'] ?? 0);
            $discount = $this->toNumber($row['discount'] ?? 0); // diskon nominal per baris
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;

            if (!$itemId || $qty <= 0) {
                // skip baris kosong
                continue;
            }

            $lineTotal = max(0, ($qty * $unitPrice) - $discount);
            $lineTotal = round($lineTotal, 2);

            /** @var PurchaseOrderLine $line */
            $line = $order->lines()->create([
                'item_id' => $itemId,
                'lot_id' => $lotId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
                'notes' => $notes,
            ]);

            $subtotal += $lineTotal;

            // Update harga terakhir per item & supplier
            $this->touchLastPrices($order, $itemId, $unitPrice);
        }

        return round($subtotal, 2);
    }

    /**
     * Hitung subtotal, tax_amount, grand_total dan simpan ke header.
     */
    protected function recalculateTotals(PurchaseOrder $order, float $subtotal): void
    {
        $discount = $this->toNumber($order->discount);
        $taxPercent = $this->toNumber($order->tax_percent);
        $shippingCost = $this->toNumber($order->shipping_cost);

        $base = max(0, $subtotal - $discount);
        $taxAmount = round($base * $taxPercent / 100, 2);
        $grandTotal = $base + $taxAmount + $shippingCost;

        $order->subtotal = round($subtotal, 2);
        $order->tax_amount = $taxAmount;
        $order->grand_total = round($grandTotal, 2);

        $order->save();
    }

    /**
     * Update:
     * - items.last_purchase_price
     * - supplier_prices.last_price
     */
    protected function touchLastPrices(PurchaseOrder $order, int $itemId, float $unitPrice): void
    {
        $unitPrice = round($unitPrice, 2);

        // Update cache di master item
        /** @var Item|null $item */
        $item = Item::find($itemId);
        if ($item) {
            $item->last_purchase_price = $unitPrice;
            $item->save();
        }

        // Update / insert harga terakhir per supplier
        SupplierPrice::updateOrCreate(
            [
                'supplier_id' => $order->supplier_id,
                'item_id' => $itemId,
            ],
            [
                'last_price' => $unitPrice,
            ]
        );
    }

    /**
     * Normalisasi angka (bisa dipakai kalau input dari form,
     * kadang ada koma / string).
     */
    protected function toNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Kalau sudah numeric (hasil validasi / cast Laravel), langsung saja
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        // Pastikan string
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // Kalau ada koma → anggap format Indonesia: "1.234,56" / "24,00"
        if (strpos($value, ',') !== false) {
            // Hilangkan titik ribuan
            $value = str_replace('.', '', $value);
            // Ganti koma jadi titik desimal
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // Kalau tidak ada koma, tapi pola ribuan: "1.234" atau "1.234.567"
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        // Default: biarkan Laravel terjemahkan (mis. "1234.56")
        return (float) $value;
    }

}
