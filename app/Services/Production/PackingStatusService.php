<?php

namespace App\Services\Production;

use App\Models\PackingJobLine;

class PackingStatusService
{
    /**
     * Ambil total qty_packed untuk 1 item di 1 warehouse sumber
     * (contoh: WH-PRD) berdasarkan status job.
     *
     * @param  int         $warehouseId   warehouse_from_id (misal: WH-PRD)
     * @param  int         $itemId        item yang dimaksud
     * @param  array|null  $statuses      status job yang dihitung (default: ['draft','posted'])
     */
    public function getPackedQtyForItem(
        int $warehouseId,
        int $itemId,
        ?array $statuses = ['draft', 'posted']
    ): float {
        return (float) PackingJobLine::query()
            ->where('item_id', $itemId)
            ->when($statuses, function ($q) use ($statuses) {
                $q->whereHas('job', function ($sub) use ($statuses) {
                    $sub->whereIn('status', $statuses);
                });
            }, function ($q) {
                // kalau statuses null, bebas status
                $q->whereHas('job');
            })
            ->whereHas('job', function ($q) use ($warehouseId) {
                $q->where('warehouse_from_id', $warehouseId);
            })
            ->sum('qty_packed');
    }

    /**
     * Ambil map [item_id => total qty_packed] untuk beberapa item di 1 warehouse.
     * Dipakai supaya tidak N+1 query di halaman list.
     *
     * @param  int         $warehouseId
     * @param  array       $itemIds
     * @param  array|null  $statuses   (default: ['draft','posted'])
     * @return array<int,float>
     */
    public function getPackedQtyMapForItems(
        int $warehouseId,
        array $itemIds,
        ?array $statuses = ['draft', 'posted']
    ): array {
        if (empty($itemIds)) {
            return [];
        }

        $rows = PackingJobLine::query()
            ->selectRaw('item_id, SUM(qty_packed) as total_packed')
            ->whereIn('item_id', $itemIds)
            ->whereHas('job', function ($q) use ($warehouseId, $statuses) {
                $q->where('warehouse_from_id', $warehouseId);

                if ($statuses) {
                    $q->whereIn('status', $statuses);
                }
            })
            ->groupBy('item_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->item_id] = (float) $row->total_packed;
        }

        return $map;
    }
}
