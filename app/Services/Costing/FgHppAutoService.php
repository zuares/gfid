<?php

namespace App\Services\Costing;

use App\Models\CuttingJobBundle;
use App\Models\ItemCostSnapshot;
use App\Models\PieceworkPayrollLine;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;

class FgHppAutoService
{
    public function __construct(
        protected InventoryService $inventory,
        protected HppService $hpp, // service pembungkus ItemCostSnapshot::create()
    ) {}

    /**
     * Hitung HPP per unit untuk 1 FG + simpan ke item_cost_snapshots.
     *
     * @param  int         $itemId       ID item finished good
     * @param  string      $dateFrom     'Y-m-d'
     * @param  string      $dateTo       'Y-m-d'
     * @param  int|null    $warehouseId  optional gudang (misal WH-RTS)
     * @param  string|null $notes        catatan snapshot
     */
    public function calculateAndSnapshotForFinishedItem(
        int $itemId,
        string $dateFrom,
        string $dateTo,
        ?int $warehouseId = null,
        ?string $notes = null,
    ): ItemCostSnapshot {
        $dateFrom = Carbon::parse($dateFrom)->toDateString();
        $dateTo = Carbon::parse($dateTo)->toDateString();

        // 1) HPP bahan baku (kain) per pcs → dari Cutting + LotCost + qty_used_fabric
        $rmUnitCost = $this->calculateRmCostPerUnitFromCutting(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 2) HPP payroll Cutting per pcs → dari PieceworkPayrollLine (module: cutting)
        $cuttingUnitCost = $this->calculateCuttingPayrollCostPerUnit(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 3) HPP payroll Sewing per pcs → dari PieceworkPayrollLine (module: sewing)
        $sewingUnitCost = $this->calculateSewingPayrollCostPerUnit(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 4) Placeholder sementara (kalau nanti ada modul finishing/packaging/overhead)
        $finishingUnitCost = 0.0;
        $packagingUnitCost = 0.0;
        $overheadUnitCost = 0.0;

        // 5) Basis qty: total qty OK sewing periode ini (konsisten dgn payroll sewing)
        $qtyBasis = $this->getFgQtyBasisFromSewingPayroll(
            finishedItemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        // 6) Simpan snapshot via HppService
        return $this->hpp->createSnapshot(
            $itemId, // item_id
            $warehouseId, // warehouse_id
            $dateTo, // snapshot_date
            'auto_hpp_period', // reference_type
            null, // reference_id
            $qtyBasis, // qty_basis
            $rmUnitCost, // rm_unit_cost
            $cuttingUnitCost, // cutting_unit_cost
            $sewingUnitCost, // sewing_unit_cost
            $finishingUnitCost,
            $packagingUnitCost,
            $overheadUnitCost,
            $notes ?: "Auto HPP {$dateFrom} s/d {$dateTo}"// notes
        );
    }

    /**
     * Hitung cost bahan baku per pcs:
     * - ambil CuttingJobBundle.finished_item_id = FG ini
     * - filter CuttingJob.date di antara periode
     * - pakai qty_used_fabric + lot avg cost (per gudang RM)
     * - dibagi total qty OK FG
     */
    protected function calculateRmCostPerUnitFromCutting(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        /** @var \Illuminate\Support\Collection<int, \App\Models\CuttingJobBundle> $bundles */
        $bundles = CuttingJobBundle::query()
            ->where('finished_item_id', $finishedItemId)
            ->whereHas('cuttingJob', function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date', [$dateFrom, $dateTo]);
            })
            ->with(['cuttingJob', 'lot'])
            ->get();

        if ($bundles->isEmpty()) {
            return 0.0;
        }

        $totalRmCost = 0.0;
        $totalFgOk = 0.0;

        foreach ($bundles as $bundle) {
            $rmQty = (float) ($bundle->qty_used_fabric ?? 0);
            if ($rmQty <= 0) {
                continue;
            }

            $fgOk = (float) $bundle->qty_cutting_ok;
            if ($fgOk <= 0) {
                continue;
            }

            $job = $bundle->cuttingJob;
            $lot = $bundle->lot;

            if (!$job || !$lot) {
                continue;
            }

            $rmWarehouseId = (int) $job->warehouse_id;
            $rmItemId = (int) $lot->item_id;
            $lotId = (int) $bundle->lot_id;

            if (!$rmWarehouseId || !$rmItemId || !$lotId) {
                continue;
            }

            $lotCost = $this->inventory->getLotMovingAverageUnitCost(
                warehouseId: $rmWarehouseId,
                itemId: $rmItemId,
                lotId: $lotId,
            );

            if ($lotCost === null) {
                continue;
            }

            $totalRmCost += $lotCost * $rmQty;
            $totalFgOk += $fgOk;
        }

        if ($totalFgOk <= 0) {
            return 0.0;
        }

        return round($totalRmCost / $totalFgOk, 4); // Rupiah per pcs
    }

    /**
     * Hitung cost payroll Cutting per pcs:
     * - PieceworkPayrollPeriod.module = 'cutting'
     * - status = final/posted
     * - periode overlapped dengan range yg diminta
     */
    private function calculateCuttingPayrollCostPerUnit(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        $lines = PieceworkPayrollLine::query()
            ->where('item_id', $finishedItemId)
            ->whereHas('payrollPeriod', function ($q) use ($dateFrom, $dateTo) {
                $q->where('module', 'cutting')
                    ->whereIn('status', ['final', 'posted'])
                    ->whereDate('period_start', '<=', $dateTo)
                    ->whereDate('period_end', '>=', $dateFrom);
            })
            ->get();

        if ($lines->isEmpty()) {
            return 0.0;
        }

        $totalAmount = (float) $lines->sum('amount');
        $totalQty = (float) $lines->sum('total_qty_ok');

        if ($totalQty <= 0.000001) {
            return 0.0;
        }

        return $totalAmount / $totalQty; // Rp/pcs
    }

    /**
     * Hitung cost payroll Sewing per pcs:
     * - PieceworkPayrollPeriod.module = 'sewing'
     * - status = final/posted
     * - periode overlapped dgn range
     */
    private function calculateSewingPayrollCostPerUnit(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        $lines = PieceworkPayrollLine::query()
            ->where('item_id', $finishedItemId)
            ->whereHas('payrollPeriod', function ($q) use ($dateFrom, $dateTo) {
                $q->where('module', 'sewing')
                    ->whereIn('status', ['final', 'posted'])
                    ->whereDate('period_start', '<=', $dateTo)
                    ->whereDate('period_end', '>=', $dateFrom);
            })
            ->get();

        if ($lines->isEmpty()) {
            return 0.0;
        }

        $totalAmount = (float) $lines->sum('amount');
        $totalQty = (float) $lines->sum('total_qty_ok');

        if ($totalQty <= 0.000001) {
            return 0.0;
        }

        return $totalAmount / $totalQty;
    }

    /**
     * Basis qty HPP = total qty OK Sewing periode ini.
     * Logika sama dgn calculateSewingPayrollCostPerUnit supaya konsisten.
     */
    protected function getFgQtyBasisFromSewingPayroll(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        $lines = PieceworkPayrollLine::query()
            ->where('item_id', $finishedItemId)
            ->whereHas('payrollPeriod', function ($q) use ($dateFrom, $dateTo) {
                $q->where('module', 'sewing')
                    ->whereIn('status', ['final', 'posted'])
                    ->whereDate('period_start', '<=', $dateTo)
                    ->whereDate('period_end', '>=', $dateFrom);
            })
            ->get();

        return (float) $lines->sum('total_qty_ok');
    }

    private function calculateFinishingCostPerUnit(
        int $finishedItemId,
        string $dateFrom,
        string $dateTo,
    ): float {
        return 0.0;
    }
}
