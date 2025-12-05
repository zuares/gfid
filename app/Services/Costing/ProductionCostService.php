<?php

namespace App\Services\Costing;

use App\Models\Item;
use App\Models\PieceworkPayrollLine;
use App\Models\ProductionCostPeriod;
use Illuminate\Support\Facades\DB;

class ProductionCostService
{
    public function __construct(
        protected HppService $hpp,
    ) {}

    /**
     * Generate costing 1 periode → buat snapshot HPP baru.
     *
     * Step:
     * 1. Ambil semua FG
     * 2. Hitung RM/unit (dari RM-only snapshot Finishing)
     * 3. Hitung biaya cutting / sewing / finishing dari payroll
     * 4. Buat snapshot HPP final (production_cost_period)
     * 5. Set snapshot ini sebagai aktif (via HppService)
     */
    public function generateFromPayroll(ProductionCostPeriod $period): array
    {
        $results = [];

        DB::transaction(function () use ($period, &$results) {

            $dateFrom = $period->date_from->toDateString();
            $dateTo = $period->date_to->toDateString();
            $snapshotDate = $period->snapshot_date->toDateString();

            // Ambil semua item FG (finished goods)
            $finishedGoods = Item::where('type', 'finished_good')->get();

            foreach ($finishedGoods as $item) {

                // 1) RM cost per pcs → dari RM-only snapshot Finishing (via HppService)
                $rmUnitCost = $this->calculateRmCostPerUnit(
                    itemId: $item->id,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                );

                // 2) Payroll Cutting / pcs
                $cuttingUnitCost = $this->calculatePayrollCostPerUnit(
                    payrollPeriodId: $period->cutting_payroll_period_id,
                    itemId: $item->id,
                );

                // 3) Payroll Sewing / pcs
                $sewingUnitCost = $this->calculatePayrollCostPerUnit(
                    payrollPeriodId: $period->sewing_payroll_period_id,
                    itemId: $item->id,
                );

                // 4) Payroll Finishing / pcs (kalau ada)
                $finishingUnitCost = $this->calculatePayrollCostPerUnit(
                    payrollPeriodId: $period->finishing_payroll_period_id,
                    itemId: $item->id,
                );

                // 5) Packaging + Overhead (sementara 0, bisa diisi nanti)
                $packagingUnitCost = 0.0;
                $overheadUnitCost = 0.0;

                // 6) Qty basis = total produksi OK di periode
                $qtyBasis = $this->getProductionQty(
                    itemId: $item->id,
                    dateFrom: $dateFrom,
                    dateTo: $dateTo,
                );

                $allCostZero = (
                    $rmUnitCost == 0.0
                    && $cuttingUnitCost == 0.0
                    && $sewingUnitCost == 0.0
                    && $finishingUnitCost == 0.0
                    && $packagingUnitCost == 0.0
                    && $overheadUnitCost == 0.0
                );

                if ($qtyBasis <= 0 && $allCostZero) {
                    // Tidak ada data sama sekali → skip snapshot untuk item ini
                    continue;
                }

                // 7) Buat SNAPSHOT HPP FINAL untuk periode ini
                //    reference_type: production_cost_period
                //    setActive: true → jadikan HPP final aktif (via HppService)
                $snapshot = $this->hpp->createSnapshot(
                    itemId: $item->id,
                    warehouseId: null, // global DULU, nanti kalau perlu bisa per gudang
                    snapshotDate: $snapshotDate,
                    referenceType: 'production_cost_period',
                    referenceId: $period->id,
                    qtyBasis: $qtyBasis,
                    rmUnitCost: $rmUnitCost,
                    cuttingUnitCost: $cuttingUnitCost,
                    sewingUnitCost: $sewingUnitCost,
                    finishingUnitCost: $finishingUnitCost,
                    packagingUnitCost: $packagingUnitCost,
                    overheadUnitCost: $overheadUnitCost,
                    notes: "Auto HPP via ProductionCostPeriod {$period->code}",
                    setActive: true, // ⬅️ di sinilah HPP final diaktifkan
                );

                $results[] = [
                    'item_code' => $item->code,
                    'item_name' => $item->name,
                    'qty_basis' => $qtyBasis,
                    'rm' => $rmUnitCost,
                    'cutting' => $cuttingUnitCost,
                    'sewing' => $sewingUnitCost,
                    'finishing' => $finishingUnitCost,
                    'packaging' => $packagingUnitCost,
                    'overhead' => $overheadUnitCost,
                    'total_hpp' => $rmUnitCost
                     + $cuttingUnitCost
                     + $sewingUnitCost
                     + $finishingUnitCost
                     + $packagingUnitCost
                     + $overheadUnitCost,
                    'snapshot_id' => $snapshot->id,
                ];
            }

            // Tandai periode sebagai posted + aktif
            $period->update([
                'status' => 'posted',
                'is_active' => true,
            ]);
        });

        return $results;
    }

    /**
     * Ambil RM cost/unit dari basis RM-only Finishing (via HppService).
     *
     * Catatan:
     * - $dateFrom saat ini belum dipakai, tapi disimpan di signature
     *   kalau nanti kamu mau bikin logic RM yang tergantung range.
     */
    protected function calculateRmCostPerUnit(int $itemId, string $dateFrom, string $dateTo): float
    {
        return $this->hpp->getRmUnitCostForItem($itemId, $dateTo);
    }

    /**
     * Hitung biaya per unit berdasarkan payroll (cutting, sewing, finishing).
     *
     * Logic:
     * - Ambil semua PieceworkPayrollLine untuk payroll_period_id + item_id
     * - total_amount / total_qty_ok → unit cost
     */
    protected function calculatePayrollCostPerUnit(?int $payrollPeriodId, int $itemId): float
    {
        if (!$payrollPeriodId) {
            return 0.0;
        }

        $lines = PieceworkPayrollLine::query()
            ->where('payroll_period_id', $payrollPeriodId)
            ->where('item_id', $itemId)
            ->get();

        if ($lines->isEmpty()) {
            return 0.0;
        }

        $totalQty = (float) $lines->sum('total_qty_ok');
        $totalAmount = (float) $lines->sum('amount');

        if ($totalQty <= 0) {
            return 0.0;
        }

        return round($totalAmount / $totalQty, 4);
    }

    /**
     * Ambil qty produksi FG OK dalam periode → dari FinishingJob (finishing_jobs + finishing_job_lines).
     */
    protected function getProductionQty(int $itemId, string $dateFrom, string $dateTo): float
    {
        return (float) DB::table('finishing_job_lines')
            ->join('finishing_jobs', 'finishing_jobs.id', '=', 'finishing_job_lines.finishing_job_id')
            ->where('finishing_jobs.status', 'posted')
            ->where('finishing_job_lines.item_id', $itemId)
            ->whereBetween('finishing_jobs.date', [$dateFrom, $dateTo])
            ->sum('finishing_job_lines.qty_ok');
    }
}
