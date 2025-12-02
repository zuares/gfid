<?php

namespace App\Services\Inventory;

use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\InventoryMutation;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockOpnameService
{
    public function __construct(
        protected InventoryService $inventory, // ⬅️ pakai InventoryService kamu
    ) {
        //
    }

    /**
     * Generate lines dari stok sistem gudang:
     * - Ambil stok per item (SUM(qty_change)) dari inventory_mutations
     * - Hanya item dengan stok != 0 kalau $onlyWithStock = true
     */
    public function generateLinesFromWarehouse(
        StockOpname $opname,
        int $warehouseId,
        bool $onlyWithStock = true
    ): void {
        // Hapus dulu lines sebelumnya kalau mau di-regenerate
        // $opname->lines()->delete();

        $query = InventoryMutation::selectRaw('item_id, SUM(qty_change) as qty')
            ->where('warehouse_id', $warehouseId)
            ->groupBy('item_id');

        if ($onlyWithStock) {
            $query->having('qty', '!=', 0);
        }

        $stocks = $query->get();

        $lines = [];

        foreach ($stocks as $row) {
            $lines[] = [
                'stock_opname_id' => $opname->id,
                'item_id' => $row->item_id,
                'system_qty' => $row->qty,
                'physical_qty' => null,
                'difference_qty' => 0,
                'is_counted' => false,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($lines)) {
            StockOpnameLine::insert($lines);
        }
    }

    /**
     * Finalize 1 dokumen Stock Opname:
     * - Buat InventoryAdjustment
     * - Buat InventoryAdjustmentLine per selisih
     * - Koreksi stok real lewat InventoryService::adjustTo()
     * - Update status opname → finalized
     */
    public function finalize(StockOpname $opname, ?string $reason = null, ?string $notes = null): InventoryAdjustment
    {
        if ($opname->status === 'finalized') {
            throw new \RuntimeException('Stock Opname sudah difinalkan.');
        }

        return DB::transaction(function () use ($opname, $reason, $notes) {
            $user = Auth::user();

            $opname->loadMissing(['warehouse', 'lines.item']);

            // Header Inventory Adjustment
            $adjustment = new InventoryAdjustment();
            $adjustment->code = $this->generateAdjustmentCode();
            $adjustment->date = $opname->date ?? now()->toDateString();
            $adjustment->warehouse_id = $opname->warehouse_id;
            $adjustment->source_type = StockOpname::class;
            $adjustment->source_id = $opname->id;
            $adjustment->reason = $reason ?: 'Penyesuaian stok dari hasil stock opname';
            $adjustment->notes = $notes;
            $adjustment->status = 'approved';
            $adjustment->created_by = $opname->created_by ?? $user?->id;
            $adjustment->approved_by = $user?->id;
            $adjustment->approved_at = now();
            $adjustment->save();

            // Detail per item (lines)
            foreach ($opname->lines as $line) {
                // Kalau qty fisik belum diisi → skip
                if ($line->physical_qty === null) {
                    continue;
                }

                $systemQty = (float) $line->system_qty;
                $physicalQty = (float) $line->physical_qty;
                $difference = $physicalQty - $systemQty;

                // Tidak ada selisih → skip
                if ($difference === 0.0) {
                    continue;
                }

                // Direction + qty_change berdasarkan selisih (snapshot)
                [$direction, $qtyChange] = $this->normalizeDifference($difference);

                // sebelum/sesudah berdasarkan data SO (snapshot)
                $qtyBefore = $systemQty;
                $qtyAfter = $physicalQty;

                // 1️⃣ Buat baris InventoryAdjustmentLine
                $adjLine = new InventoryAdjustmentLine();
                $adjLine->inventory_adjustment_id = $adjustment->id;
                $adjLine->item_id = $line->item_id;
                $adjLine->qty_before = $qtyBefore;
                $adjLine->qty_after = $qtyAfter;
                $adjLine->qty_change = $qtyChange;
                $adjLine->direction = $direction;
                $adjLine->notes = $line->notes; // catatan per item dari opname
                $adjLine->save();

                // 2️⃣ Koreksi stok real lewat InventoryService (akan:
                //     - update inventory_stocks
                //     - catat inventory_mutations)
                $this->inventory->adjustTo(
                    warehouseId: $opname->warehouse_id,
                    itemId: $line->item_id,
                    newQty: $physicalQty,
                    date: $adjustment->date,
                    sourceType: InventoryAdjustment::class,
                    sourceId: $adjustment->id,
                    notes: $adjustment->reason,
                    lotId: null, // opname level item, bukan per LOT
                );
            }

            // Update status opname → finalized
            $opname->status = 'finalized';
            $opname->finalized_by = $user?->id;
            $opname->finalized_at = now();
            $opname->save();

            return $adjustment;
        });
    }

    /**
     * Normalisasi selisih:
     * > 0  → direction = in,  qtyChange = selisih
     * < 0  → direction = out, qtyChange = |selisih|
     */
    protected function normalizeDifference(float $difference): array
    {
        if ($difference > 0) {
            return ['in', $difference];
        }

        return ['out', abs($difference)];
    }

    /**
     * Generate kode ADJ sederhana: ADJ-YYYYMMDD-XXXX
     */
    protected function generateAdjustmentCode(): string
    {
        $date = Carbon::now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "ADJ-{$date}-{$random}";
    }
}
