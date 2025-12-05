<?php

namespace App\Services\Costing;

use App\Models\ItemCostSnapshot;
use App\Models\ProductionCostPeriod;

class HppService
{
    /**
     * Buat 1 snapshot HPP untuk FG.
     *
     * Catatan:
     * - Default is_active = false (snapshot historis / RM-only).
     * - Kalau $setActive = true → akan otomatis memanggil setActiveSnapshot()
     *   sehingga snapshot ini jadi HPP aktif (dan snapshot lain dinonaktifkan
     *   sesuai aturan di setActiveSnapshot()).
     */
    public function createSnapshot(
        int $itemId,
        ?int $warehouseId,
        string $snapshotDate,
        string $referenceType,
        ?int $referenceId,
        float $qtyBasis,
        float $rmUnitCost,
        float $cuttingUnitCost,
        float $sewingUnitCost,
        float $finishingUnitCost,
        float $packagingUnitCost,
        float $overheadUnitCost,
        ?string $notes = null,
        bool $setActive = false,
    ): ItemCostSnapshot {
        $unitCost = $rmUnitCost
             + $cuttingUnitCost
             + $sewingUnitCost
             + $finishingUnitCost
             + $packagingUnitCost
             + $overheadUnitCost;

        $snapshot = ItemCostSnapshot::create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'snapshot_date' => $snapshotDate,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'qty_basis' => $qtyBasis,
            'rm_unit_cost' => $rmUnitCost,
            'cutting_unit_cost' => $cuttingUnitCost,
            'sewing_unit_cost' => $sewingUnitCost,
            'finishing_unit_cost' => $finishingUnitCost,
            'packaging_unit_cost' => $packagingUnitCost,
            'overhead_unit_cost' => $overheadUnitCost,
            'unit_cost' => $unitCost,
            'notes' => $notes,
            'is_active' => false, // default: non-aktif
            'created_by' => auth()->id(),
        ]);

        if ($setActive) {
            $this->setActiveSnapshot($snapshot);
        }

        return $snapshot;
    }

    /**
     * Jadikan satu snapshot sebagai HPP aktif.
     *
     * - $exclusiveWithinType = true:
     *   hanya menonaktifkan snapshot lain dengan item_id & reference_type sama.
     *   (cocok untuk production_cost_period → HPP final per periode)
     *
     * - Kalau mau lebih agresif, kamu bisa ganti logic query di sini.
     */
    public function setActiveSnapshot(ItemCostSnapshot $snapshot, bool $exclusiveWithinType = true): void
    {
        $query = ItemCostSnapshot::query()
            ->where('item_id', $snapshot->item_id);

        if ($exclusiveWithinType) {
            $query->where('reference_type', $snapshot->reference_type);
        }

        if (!is_null($snapshot->warehouse_id)) {
            $query->where('warehouse_id', $snapshot->warehouse_id);
        }

        $query->where('id', '!=', $snapshot->id)
            ->update(['is_active' => false]);

        $snapshot->is_active = true;
        $snapshot->save();
    }

    /**
     * Ambil snapshot "basis RM" untuk item tertentu.
     *
     * Prioritas:
     *  1. Snapshot RM-only dari Finishing (auto_hpp_rm_only_finishing)
     *  2. Fallback: snapshot lain (bukan production_cost_period) yang punya RM / unit_cost > 0
     */
    public function getRmBaseSnapshotForItem(int $itemId, string $dateTo): ?ItemCostSnapshot
    {
        // 1️⃣ coba pakai RM-only finishing
        $rmOnly = ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->where('reference_type', 'auto_hpp_rm_only_finishing')
            ->whereDate('snapshot_date', '<=', $dateTo)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        if ($rmOnly) {
            return $rmOnly;
        }

        // 2️⃣ fallback: snapshot lain yang punya nilai RM / unit_cost, tapi BUKAN dari production_cost_period
        return ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->where('reference_type', '!=', 'production_cost_period')
            ->where(function ($q) {
                $q->where('rm_unit_cost', '>', 0)
                    ->orWhere('unit_cost', '>', 0);
            })
            ->whereDate('snapshot_date', '<=', $dateTo)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Ambil angka RM/unit saja (float) untuk dipakai di ProductionCostService.
     */
    public function getRmUnitCostForItem(int $itemId, string $dateTo): float
    {
        $snapshot = $this->getRmBaseSnapshotForItem($itemId, $dateTo);

        if (!$snapshot) {
            return 0.0;
        }

        return (float) ($snapshot->rm_unit_cost ?? $snapshot->unit_cost ?? 0.0);
    }

    /**
     * Ambil HPP final aktif untuk item (dipakai modul Sales, laporan laba rugi, dll).
     *
     * - Mengutamakan HPP dari ProductionCostPeriod yang is_active = true.
     * - Kalau tidak ada, fallback ke snapshot is_active lain (legacy).
     */
    public function getActiveFinalHppForItem(int $itemId, ?int $warehouseId = null): ?ItemCostSnapshot
    {
        // 1️⃣ cek periode costing aktif
        $currentPeriod = ProductionCostPeriod::query()
            ->where('is_active', true)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        $query = ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->where('is_active', true);

        if ($currentPeriod) {
            // batasi ke HPP final dari periode ini
            $query->where('reference_type', 'production_cost_period')
                ->where('reference_id', $currentPeriod->id);
        } else {
            // kalau tidak ada periode aktif, tetap utamakan yang dari production_cost_period
            $query->where('reference_type', 'production_cost_period');
        }

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->whereNull('warehouse_id')
                    ->orWhere('warehouse_id', $warehouseId);
            })->orderByDesc('warehouse_id');
        }

        $snapshot = $query
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        // 2️⃣ fallback: kalau tidak ketemu (mis. sistem lama)
        if (!$snapshot) {
            $fallback = ItemCostSnapshot::query()
                ->where('item_id', $itemId)
                ->where('is_active', true);

            if ($warehouseId) {
                $fallback->where(function ($q) use ($warehouseId) {
                    $q->whereNull('warehouse_id')
                        ->orWhere('warehouse_id', $warehouseId);
                })->orderByDesc('warehouse_id');
            }

            $snapshot = $fallback
                ->orderByDesc('snapshot_date')
                ->orderByDesc('id')
                ->first();
        }

        return $snapshot;
    }

    /**
     * Backward-compatible helper:
     * sebelumnya kamu pakai getActiveSnapshotForItem() sebagai pintu utama.
     * Sekarang diarahkan ke getActiveFinalHppForItem().
     */
    public function getActiveSnapshotForItem(int $itemId, ?int $warehouseId = null): ?ItemCostSnapshot
    {
        return $this->getActiveFinalHppForItem($itemId, $warehouseId);
    }
}
