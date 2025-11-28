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
    public static function generate($periodStart, $periodEnd, ?int $createdByUserId = null): PieceworkPayrollPeriod
    {
        // Normalisasi tanggal ke Carbon
        $start = Carbon::parse($periodStart)->startOfDay();
        $end = Carbon::parse($periodEnd)->endOfDay();

        return DB::transaction(function () use ($start, $end, $createdByUserId) {
            // 1. Buat / simpan periode payroll baru (status draft)
            $period = PieceworkPayrollPeriod::create([
                'module' => 'cutting',
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => 'draft',
                'notes' => null,
                'created_by' => $createdByUserId,
            ]);

            // 2. Ambil summary qty_qc_ok per operator + kategori + item
            //    Dari cutting_job_bundles join cutting_jobs & items
            $rows = CuttingJobBundle::query()
                ->join('cutting_jobs', 'cutting_job_bundles.cutting_job_id', '=', 'cutting_jobs.id')
                ->join('items', 'cutting_job_bundles.finished_item_id', '=', 'items.id')
            // filter tanggal & status job
                ->whereBetween('cutting_jobs.date', [$start->toDateString(), $end->toDateString()])
                ->where('cutting_jobs.status', 'done')
            // hanya yang qty_qc_ok > 0
                ->where('cutting_job_bundles.qty_qc_ok', '>', 0)
            // ambil operator: pakai operator bundle kalau ada, kalau null pakai operator job
                ->selectRaw('
                    COALESCE(cutting_job_bundles.operator_id, cutting_jobs.operator_id) as employee_id,
                    items.item_category_id as item_category_id,
                    cutting_job_bundles.finished_item_id as item_id,
                    SUM(cutting_job_bundles.qty_qc_ok) as total_qty_ok
                ')
                ->groupBy('employee_id', 'item_category_id', 'item_id')
                ->get();

            // Kalau tidak ada data, tetap kembalikan period dengan lines kosong
            if ($rows->isEmpty()) {
                return $period;
            }

            foreach ($rows as $row) {
                $employeeId = (int) $row->employee_id;
                $itemCategoryId = $row->item_category_id ? (int) $row->item_category_id : null;
                $itemId = $row->item_id ? (int) $row->item_id : null;
                $totalQtyOk = (float) $row->total_qty_ok;

                if (!$employeeId || $totalQtyOk <= 0) {
                    continue;
                }

                // 3. Cari tarif dari piece_rates (module= cutting)
                $rate = self::resolveRateForCutting(
                    employeeId: $employeeId,
                    itemCategoryId: $itemCategoryId,
                    itemId: $itemId,
                    atDate: $end->toDateString()
                );

                // 4. Hitung amount = qty * rate
                $amount = $totalQtyOk * $rate;

                // 5. Simpan ke piecework_payroll_lines
                PieceworkPayrollLine::create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employeeId,
                    'item_category_id' => $itemCategoryId,
                    'item_id' => $itemId,
                    'total_qty_ok' => $totalQtyOk,
                    'rate_per_pcs' => $rate,
                    'amount' => $amount,
                ]);
            }

            return $period->fresh('lines');
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
    protected static function resolveRateForCutting(
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
