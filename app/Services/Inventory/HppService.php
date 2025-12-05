<?php

namespace App\Services\Costing;

use App\Models\ItemCostSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HppService
{
    /**
     * Hitung HPP Cutting dari total nilai RM / lot yang dipakai.
     */
    public function calculateCuttingHpp(
        float | int | string $rmTotalCost,
        float | int | string $totalQtyOk
    ): float {
        $rmTotalCost = $this->num($rmTotalCost);
        $totalQtyOk = $this->num($totalQtyOk);

        if ($totalQtyOk <= 0) {
            throw new \RuntimeException('Qty OK harus > 0 untuk hitung HPP Cutting.');
        }

        return round($rmTotalCost / $totalQtyOk, 4);
    }

    /**
     * HPP Sewing = HPP Cutting + biaya sewing (piece rate per unit).
     */
    public function calculateSewingHpp(
        float | int | string $cuttingUnitCost,
        float | int | string $sewingLaborPerUnit
    ): float {
        $cutting = $this->num($cuttingUnitCost);
        $sewingLabor = $this->num($sewingLaborPerUnit);

        return round($cutting + $sewingLabor, 4);
    }

    /**
     * HPP Finishing = HPP Sewing + biaya finishing per unit.
     */
    public function calculateFinishingHpp(
        float | int | string $sewingUnitCost,
        float | int | string $finishingPerUnit
    ): float {
        $sewing = $this->num($sewingUnitCost);
        $finishing = $this->num($finishingPerUnit);

        return round($sewing + $finishing, 4);
    }

    /**
     * HPP Packaging = biaya packaging per unit (langsung).
     */
    public function calculatePackagingHpp(float | int | string $packagingPerUnit): float
    {
        return round($this->num($packagingPerUnit), 4);
    }

    /**
     * Hitung total HPP final (FG) dari semua komponen per unit.
     *
     * Komponen bisa berupa:
     * ['rm' => .., 'cutting' => .., 'sewing' => .., 'finishing' => .., 'packaging' => .., 'overhead' => ..]
     * atau array numeric biasa [rm, cutting, sewing, finishing, packaging, overhead]
     */
    public function calculateTotalHpp(array $components): float
    {
        $total = 0.0;

        foreach ($components as $value) {
            $total += $this->num($value);
        }

        return round($total, 4);
    }

    /**
     * Simpan snapshot HPP satu item ke tabel item_cost_snapshots.
     *
     * $data minimal berisi:
     *  - item_id (int)
     *  - warehouse_id (int|null)
     *  - snapshot_date (string|\DateTimeInterface|null) → default: today
     *  - reference_type (string|null)
     *  - reference_id (int|null)
     *  - qty_basis (float|null)
     *  - rm_unit_cost, cutting_unit_cost, sewing_unit_cost,
     *    finishing_unit_cost, packaging_unit_cost, overhead_unit_cost
     *  - notes (string|null)
     *  - is_active (bool|null) → default false
     */
    public function createSnapshot(array $data): ItemCostSnapshot
    {
        $snapshotDate = $this->normalizeDate($data['snapshot_date'] ?? null);

        $rm = $this->num($data['rm_unit_cost'] ?? 0);
        $cutting = $this->num($data['cutting_unit_cost'] ?? 0);
        $sewing = $this->num($data['sewing_unit_cost'] ?? 0);
        $finishing = $this->num($data['finishing_unit_cost'] ?? 0);
        $packaging = $this->num($data['packaging_unit_cost'] ?? 0);
        $overhead = $this->num($data['overhead_unit_cost'] ?? 0);

        $totalUnitCost = $this->calculateTotalHpp([
            $rm,
            $cutting,
            $sewing,
            $finishing,
            $packaging,
            $overhead,
        ]);

        $snapshot = new ItemCostSnapshot();
        $snapshot->item_id = (int) $data['item_id'];
        $snapshot->warehouse_id = isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;
        $snapshot->snapshot_date = $snapshotDate;
        $snapshot->reference_type = $data['reference_type'] ?? null;
        $snapshot->reference_id = $data['reference_id'] ?? null;

        // qty_basis di DB NOT NULL + default 0, jadi aman kalau kita pakai 0 kalau nggak dikirim
        $snapshot->qty_basis = $this->num($data['qty_basis'] ?? 0);

        $snapshot->rm_unit_cost = $rm;
        $snapshot->cutting_unit_cost = $cutting;
        $snapshot->sewing_unit_cost = $sewing;
        $snapshot->finishing_unit_cost = $finishing;
        $snapshot->packaging_unit_cost = $packaging;
        $snapshot->overhead_unit_cost = $overhead;

        // 🧠 sesuai schema:  kolom total HPP per unit = `unit_cost`
        $snapshot->unit_cost = $totalUnitCost;

        $snapshot->notes = $data['notes'] ?? null;
        $snapshot->is_active = (bool) ($data['is_active'] ?? false);
        $snapshot->created_by = Auth::id();

        $snapshot->save();

        return $snapshot;
    }

    /**
     * Helper: normalisasi angka (kayak di InventoryService::num()).
     */
    protected function num(float | int | string | null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        if (strpos($value, ',') !== false) {
            // Format Indonesia: 1.234,56
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        return (float) $value;
    }

    protected function normalizeDate(string | \DateTimeInterface  | null $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->toDateString();
        }

        if (is_string($date) && trim($date) !== '') {
            return Carbon::parse($date)->toDateString();
        }

        return now()->toDateString();
    }

    /**
     * Ambil snapshot HPP aktif terbaru (tanpa peduli tanggal),
     * misal buat info "HPP aktif saat ini".
     */
    public function getActiveSnapshotForItem(int $itemId, ?int $warehouseId = null): ?ItemCostSnapshot
    {
        return ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('is_active', true)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Ambil snapshot HPP yang dipakai untuk penjualan per tanggal:
     * - is_active = true
     * - snapshot_date <= tanggal invoice
     * - kalau banyak, ambil yang paling baru.
     */
    public function getSnapshotForSale(
        int $itemId,
        ?int $warehouseId,
        string $saleDate,
    ): ?ItemCostSnapshot {
        return ItemCostSnapshot::query()
            ->where('item_id', $itemId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('is_active', true)
            ->whereDate('snapshot_date', '<=', $saleDate)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Helper: balikin angka HPP/unit untuk penjualan,
     * default 0 kalau nggak ketemu snapshot.
     */
    public function getUnitCostForSale(
        int $itemId,
        ?int $warehouseId,
        string $saleDate,
    ): float {
        $snap = $this->getSnapshotForSale($itemId, $warehouseId, $saleDate);

        return $snap?->unit_cost ?? 0.0;
    }
}
