<?php

namespace App\Services\Payroll;

use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\PieceRate;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CuttingPayrollGenerator
{
    /**
     * Generate payroll borongan untuk modul Cutting
     *
     * @param  string|\DateTimeInterface  $periodStart
     * @param  string|\DateTimeInterface  $periodEnd
     * @param  int|null $createdByUserId
     * @return \App\Models\PieceworkPayrollPeriod
     */

    public static function generate(
        $periodStart,
        $periodEnd,
        ?int $createdByUserId = null,
        ?PieceworkPayrollPeriod $existingPeriod = null,
    ): PieceworkPayrollPeriod {
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();

        return DB::transaction(function () use ($start, $end, $createdByUserId, $existingPeriod) {
            // 1. Siapkan / pakai period
            if ($existingPeriod) {
                $period = $existingPeriod->fresh();
                $period->lines()->delete();
                $period->total_amount = 0;
                $period->save();
            } else {
                $period = PieceworkPayrollPeriod::create([
                    'module' => 'cutting',
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'status' => 'draft',
                    'created_by' => $createdByUserId,
                    'total_amount' => 0,
                ]);
            }

            // 2. Agregasi dari Cutting Job + Bundles
            $rows = CuttingJobBundle::query()
                ->join('cutting_jobs', 'cutting_job_bundles.cutting_job_id', '=', 'cutting_jobs.id')
                ->join('items', 'cutting_job_bundles.finished_item_id', '=', 'items.id')
            // filter tanggal & status job
                ->whereBetween('cutting_jobs.date', [$start, $end])
                ->where('cutting_jobs.status', 'qc_done')
            // hanya yang qty_qc_ok > 0
                ->where('cutting_job_bundles.qty_qc_ok', '>', 0)
            // agregasi operator, kategori, item
                ->selectRaw('
                COALESCE(cutting_job_bundles.operator_id, cutting_jobs.operator_id) as employee_id,
                COALESCE(cutting_job_bundles.item_category_id, items.item_category_id) as item_category_id,
                cutting_job_bundles.finished_item_id as item_id,
                SUM(cutting_job_bundles.qty_qc_ok) as total_qty_ok
            ')
                ->groupByRaw('
                COALESCE(cutting_job_bundles.operator_id, cutting_jobs.operator_id),
                COALESCE(cutting_job_bundles.item_category_id, items.item_category_id),
                cutting_job_bundles.finished_item_id
            ')
                ->get();

            $totalAmount = 0;
            $atDate = $end->toDateString();

            foreach ($rows as $row) {
                // pakai helper yang sudah kamu buat
                $rateValue = self::resolveRateForCutting(
                    employeeId: $row->employee_id,
                    itemCategoryId: $row->item_category_id,
                    itemId: $row->item_id,
                    atDate: $atDate,
                );

                $amount = $rateValue * (float) $row->total_qty_ok;
                $totalAmount += $amount;

                PieceworkPayrollLine::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $row->employee_id,
                    'item_category_id' => $row->item_category_id, // ⬅️ sekarang KEISI
                    'item_id' => $row->item_id,
                    'total_qty_ok' => $row->total_qty_ok,
                    'rate_per_pcs' => $rateValue,
                    'amount' => $amount,
                ]);
            }

            // 3. Update total
            $period->update([
                'total_amount' => $totalAmount,
            ]);

            return $period->fresh(['lines']);
        });
    }

    /**
     * Ambil tarif borongan per pcs untuk Cutting.
     *
     * Prioritas:
     *  1) piece_rates dengan module='cutting' + employee + item_id (paling spesifik)
     *  2) piece_rates dengan module='cutting' + employee + item_category_id (fallback)
     *  3) employee.default_piece_rate (kalau master kosong)
     *
     * @param  int         $employeeId
     * @param  int|null    $itemCategoryId
     * @param  int|null    $itemId
     * @param  string      $atDate (YYYY-MM-DD)
     * @return float
     */
    public static function resolveRateForCutting(
        int $employeeId,
        ?int $itemCategoryId,
        ?int $itemId,
        string $atDate
    ): float {
        $query = PieceRate::query()
            ->where('module', 'cutting')
            ->where('employee_id', $employeeId)
        // masa berlaku (effective)
            ->where('effective_from', '<=', $atDate)
            ->where(function ($q) use ($atDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $atDate);
            });

        // Cari yang paling spesifik dulu: by item_id, kalau tidak ada jatuh ke category
        $query->where(function ($q) use ($itemId, $itemCategoryId) {
            if ($itemId) {
                $q->where('item_id', $itemId);
            }

            if ($itemCategoryId) {
                // rule kategori (item_id null, kategori cocok)
                $q->orWhere(function ($q2) use ($itemCategoryId) {
                    $q2->whereNull('item_id')
                        ->where('item_category_id', $itemCategoryId);
                });
            }
        });

        // Urutkan supaya item_spesifik didahulukan
        $pieceRate = $query
            ->orderByDesc('item_id') // item_id NOT NULL lebih diutamakan
            ->orderByDesc('item_category_id') // lalu kategori
            ->first();

        if ($pieceRate) {
            return (float) $pieceRate->rate_per_pcs;
        }

        // Fallback: default_piece_rate dari employees
        $employee = Employee::find($employeeId);
        if ($employee && $employee->default_piece_rate) {
            return (float) $employee->default_piece_rate;
        }

        // Terakhir: 0 kalau benar-benar tidak ada
        return 0.0;
    }
}
