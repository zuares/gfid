<?php

namespace App\Console\Commands;

use App\Services\Payroll\CuttingPayrollGenerator;
use Illuminate\Console\Command;

class GenerateCuttingPayroll extends Command
{
    protected $signature = 'payroll:cutting {start} {end} {--user=1}';
    protected $description = 'Generate payroll borongan Cutting untuk periode tertentu';

    public function handle(): int
    {
        $start = $this->argument('start');
        $end = $this->argument('end');
        $userId = (int) $this->option('user');

        $this->info("Generate Payroll Cutting {$start} s/d {$end} ...");

        $period = CuttingPayrollGenerator::generate(
            periodStart: $start,
            periodEnd: $end,
            createdByUserId: $userId
        );

        $this->info("Periode ID: {$period->id}");
        $this->info("Periode: {$period->period_start} s/d {$period->period_end}");
        $this->info("Status: {$period->status}");

        $lines = $period->lines;
        $totalAmount = $lines->sum('amount');

        $this->line("Total lines : " . $lines->count());
        $this->line("Total amount: " . number_format($totalAmount, 0, ',', '.'));

        return Command::SUCCESS;
    }
}
