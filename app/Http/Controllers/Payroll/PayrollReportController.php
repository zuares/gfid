<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PieceworkPayrollLine;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PayrollReportController extends Controller
{
    /**
     * Rekap mingguan / bulanan per operator.
     *
     * Filter:
     * - module: cutting | sewing | all
     * - range_type: weekly | monthly
     * - ref_date: tanggal referensi (YYYY-MM-DD), default: today
     * - sort: name | amount
     */
    public function operatorSummary(Request $request): View
    {
        $module = $request->input('module', 'cutting'); // cutting | sewing | all
        $rangeType = $request->input('range_type', 'monthly'); // weekly | monthly
        $refDate = $request->input('ref_date', Carbon::today()->toDateString());
        $sort = $request->input('sort', 'name'); // name | amount

        // Normalisasi input
        if (!in_array($module, ['cutting', 'sewing', 'all'], true)) {
            $module = 'cutting';
        }
        if (!in_array($rangeType, ['weekly', 'monthly'], true)) {
            $rangeType = 'monthly';
        }

        $ref = Carbon::parse($refDate);

        // Tentukan range tanggal
        if ($rangeType === 'weekly') {
            $start = $ref->copy()->startOfWeek(Carbon::MONDAY);
            $end = $ref->copy()->endOfWeek(Carbon::SUNDAY);
            $labelRange = 'Mingguan: ' . id_date($start) . ' s/d ' . id_date($end);
        } else {
            $start = $ref->copy()->startOfMonth();
            $end = $ref->copy()->endOfMonth();
            $labelRange = 'Bulanan: '
            . $ref->locale('id')->translatedFormat('F Y')
            . ' (' . id_date($start) . ' s/d ' . id_date($end) . ')';
        }

        // Ambil semua line payroll yang periode-nya overlap dengan range
        $lines = PieceworkPayrollLine::query()
            ->with(['employee', 'period'])
            ->whereHas('period', function ($q) use ($module, $start, $end) {
                // Modul: kalau "all" jangan filter module
                if ($module !== 'all') {
                    $q->where('module', $module);
                }

                // Overlap periode payroll dengan range tanggal
                $q->where('period_start', '<=', $end->toDateString())
                    ->where('period_end', '>=', $start->toDateString());
            })
            ->get();

        // Flag untuk sembunyi opsi modul di filter kalau memang tidak ada datanya
        $hasCutting = $lines->contains(fn($line) => $line->period?->module === 'cutting');
        $hasSewing = $lines->contains(fn($line) => $line->period?->module === 'sewing');

        // Group per operator + hitung breakdown CUTTING / SEWING
        $byEmployee = $lines
            ->groupBy('employee_id')
            ->map(function ($group) {
                $employee = $group->first()->employee;

                $cuttingLines = $group->filter(fn($line) => $line->period?->module === 'cutting');
                $sewingLines = $group->filter(fn($line) => $line->period?->module === 'sewing');

                $cuttingQty = (float) $cuttingLines->sum('total_qty_ok');
                $cuttingAmount = (float) $cuttingLines->sum('amount');
                $sewingQty = (float) $sewingLines->sum('total_qty_ok');
                $sewingAmount = (float) $sewingLines->sum('amount');

                // Harga satuan = rata-rata tertimbang (amount / qty), kalau qty > 0
                $cuttingRate = $cuttingQty > 0
                ? $cuttingAmount / $cuttingQty
                : 0.0;

                $sewingRate = $sewingQty > 0
                ? $sewingAmount / $sewingQty
                : 0.0;

                $totalAmount = $cuttingAmount + $sewingAmount;
                $totalQty = $cuttingQty + $sewingQty;

                return [
                    'employee_id' => $employee?->id,
                    'employee_name' => $employee?->name ?? '-',

                    'cutting_qty' => $cuttingQty,
                    'cutting_amount' => $cuttingAmount,
                    'cutting_rate' => $cuttingRate,

                    'sewing_qty' => $sewingQty,
                    'sewing_amount' => $sewingAmount,
                    'sewing_rate' => $sewingRate,

                    'total_qty' => $totalQty,
                    'total_amount' => $totalAmount,
                ];
            });

        // Sorting: by nama atau nominal terbesar
        if ($sort === 'amount') {
            $byEmployee = $byEmployee->sortByDesc('total_amount');
        } else {
            $byEmployee = $byEmployee->sortBy('employee_name');
        }

        $byEmployee = $byEmployee->values();

        // Grand total
        $grandTotalQty = (float) $lines->sum('total_qty_ok');
        $grandTotalAmount = (float) $lines->sum('amount');

        return view('payroll.reports.operator_summary', [
            'module' => $module,
            'rangeType' => $rangeType,
            'refDate' => $refDate,
            'start' => $start,
            'end' => $end,
            'labelRange' => $labelRange,

            'byEmployee' => $byEmployee,
            'grandTotalQty' => $grandTotalQty,
            'grandTotalAmount' => $grandTotalAmount,

            'hasCutting' => $hasCutting,
            'hasSewing' => $hasSewing,
            'sort' => $sort,
        ]);
    }

    public function operatorSlips(Request $request): View
    {
        $module = $request->input('module', 'cutting'); // cutting | sewing | all
        $rangeType = $request->input('range_type', 'monthly'); // weekly | monthly
        $refDate = $request->input('ref_date', Carbon::today()->toDateString());

        if (!in_array($module, ['cutting', 'sewing', 'all'], true)) {
            $module = 'cutting';
        }
        if (!in_array($rangeType, ['weekly', 'monthly'], true)) {
            $rangeType = 'monthly';
        }

        $ref = Carbon::parse($refDate);

        if ($rangeType === 'weekly') {
            $start = $ref->copy()->startOfWeek(Carbon::MONDAY);
            $end = $ref->copy()->endOfWeek(Carbon::SUNDAY);
            $labelRange = 'Mingguan: ' . id_date($start) . ' s/d ' . id_date($end);
        } else {
            $start = $ref->copy()->startOfMonth();
            $end = $ref->copy()->endOfMonth();
            $labelRange = 'Bulanan: '
            . $ref->locale('id')->translatedFormat('F Y')
            . ' (' . id_date($start) . ' s/d ' . id_date($end) . ')';
        }

        // Ambil semua line dalam range (modul bisa all)
        $lines = PieceworkPayrollLine::query()
            ->with(['employee', 'category', 'item', 'period'])
            ->whereHas('period', function ($q) use ($module, $start, $end) {
                if ($module !== 'all') {
                    $q->where('module', $module);
                }

                $q->where('period_start', '<=', $end->toDateString())
                    ->where('period_end', '>=', $start->toDateString());
            })
            ->get();

        // Group by operator → slip per operator
        /** @var \Illuminate\Support\Collection $grouped */
        $grouped = $lines
            ->groupBy('employee_id')
            ->map(function (Collection $group) {
                $employee = $group->first()->employee;

                $details = $group->map(function (PieceworkPayrollLine $line) {
                    $moduleName = $line->period?->module === 'cutting'
                    ? 'Cutting'
                    : ($line->period?->module === 'sewing'
                        ? 'Sewing'
                        : strtoupper($line->period?->module ?? '-'));

                    return [
                        'module' => $moduleName,
                        'category' => $line->category?->name,
                        'item_code' => $line->item?->code,
                        'item_name' => $line->item?->name,
                        'qty' => (float) $line->total_qty_ok,
                        'rate' => (float) $line->rate_per_pcs,
                        'amount' => (float) $line->amount,
                    ];
                });

                return [
                    'employee_id' => $employee?->id,
                    'employee_name' => $employee?->name ?? '-',
                    'details' => $details,
                    'total_amount' => (float) $group->sum('amount'),
                ];
            })
            ->sortBy('employee_name')
            ->values();

        return view('payroll.reports.operator_slips', [
            'module' => $module,
            'rangeType' => $rangeType,
            'refDate' => $refDate,
            'start' => $start,
            'end' => $end,
            'labelRange' => $labelRange,
            'slips' => $grouped,
        ]);
    }

    public function operatorDetail(Request $request, $employeeId)
    {
        // Modul dari query: cutting / sewing / all (default all)
        $module = $request->input('module', 'all');
        if (!in_array($module, ['cutting', 'sewing', 'all'], true)) {
            $module = 'all';
        }

        // Tetap baca range_type & ref_date hanya untuk label (biar UI-nya konsisten)
        $rangeType = $request->input('range_type', 'monthly');
        $refDate = $request->input('ref_date', now()->toDateString());
        $ref = Carbon::parse($refDate);

        if ($rangeType === 'weekly') {
            $labelRange = 'Mingguan (riwayat semua periode)';
        } else {
            $labelRange = 'Bulanan (riwayat semua periode)';
        }

        // EMPLOYEE
        $employee = Employee::findOrFail($employeeId);

        // 🔑 Ambil SEMUA line payroll untuk employee ini
        $lines = PieceworkPayrollLine::with(['period', 'category', 'item'])
            ->where('employee_id', $employeeId)
            ->whereHas('period', function ($q) use ($module) {
                if ($module !== 'all') {
                    $q->where('module', $module);
                }
            })
            ->get();

        // Pisah cutting & sewing dari period.module
        $cutting = $lines->where('period.module', 'cutting');
        $sewing = $lines->where('period.module', 'sewing');

        // Summary
        $summary = [
            'cutting_qty' => (float) $cutting->sum('total_qty_ok'),
            'cutting_amount' => (float) $cutting->sum('amount'),
            'sewing_qty' => (float) $sewing->sum('total_qty_ok'),
            'sewing_amount' => (float) $sewing->sum('amount'),
            'grand_qty' => (float) $lines->sum('total_qty_ok'),
            'grand_amount' => (float) $lines->sum('amount'),
        ];

        // Group detail per hari pakai tanggal period_start dari periode payroll
        $cuttingDaily = $cutting->groupBy(fn($l) => $l->period->period_start);
        $sewingDaily = $sewing->groupBy(fn($l) => $l->period->period_start);

        return view('payroll.reports.operator_detail', [
            'employee' => $employee,
            'labelRange' => $labelRange,
            'rangeType' => $rangeType,
            'refDate' => $refDate,
            'summary' => $summary,
            'cuttingDaily' => $cuttingDaily,
            'sewingDaily' => $sewingDaily,
            'module' => $module,
        ]);
    }

}
