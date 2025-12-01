<?php

namespace App\Console\Commands;

use App\Services\Payroll\SewingPayrollGenerator;
use Illuminate\Console\Command;

class GenerateSewingPayroll extends Command
{
    protected $signature = 'payroll:sewing {start} {end} {--user=1}';
    protected $description = 'Generate payroll borongan Sewing untuk periode tertentu';

    public function handle(): int
    {
        $start = $this->argument('start');
        $end = $this->argument('end');
        $userId = (int) $this->option('user');

        $this->info("Generate Payroll Sewing {$start} s/d {$end} ...");

        $period = SewingPayrollGenerator::generate(
            periodStart: $start,
            periodEnd: $end,
            createdByUserId: $userId
        );

        // 👇 Ringkasan hasil
        $lines = $period->lines()->with('employee')->get();
        $totalLines = $lines->count();
        $totalAmount = $lines->sum('amount');

        $this->line('-------------------------------');
        $this->line("Periode ID      : {$period->id}");
        $this->line("Periode tanggal : {$period->period_start} s/d {$period->period_end}");
        $this->line("Status          : {$period->status}");
        $this->line("Total baris     : {$totalLines}");
        $this->line("Total amount    : " . number_format($totalAmount, 0, ',', '.'));
        $this->line('-------------------------------');

        // Ringkasan per operator
        if ($totalLines > 0) {
            $this->line('Ringkasan per operator:');

            $grouped = $lines->groupBy('employee_id');

            foreach ($grouped as $employeeId => $group) {
                $name = $group->first()->employee->name ?? "ID {$employeeId}";
                $qty = $group->sum('total_qty_ok');
                $amount = $group->sum('amount');

                $this->line(
                    "- {$name}: Qty " .
                    number_format($qty, 2, ',', '.') .
                    ' | Amount ' .
                    number_format($amount, 0, ',', '.')
                );
            }
        } else {
            $this->warn('Tidak ada data Sewing Return (qty_ok > 0) yang masuk payroll untuk periode ini.');
        }

        return Command::SUCCESS;
    }
}
