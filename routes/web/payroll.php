<?php

use App\Http\Controllers\Payroll\CuttingPayrollController;
use App\Http\Controllers\Payroll\PayrollReportController;
use App\Http\Controllers\Payroll\PieceRateController;
use App\Http\Controllers\Payroll\SewingPayrollController;

// Semua route payroll: hanya bisa diakses oleh owner
Route::middleware(['web', 'auth', 'role:owner'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | CUTTING PAYROLL
    |--------------------------------------------------------------------------
     */
    Route::prefix('payroll/cutting')
        ->name('payroll.cutting.')
        ->group(function () {
            Route::get('/', [CuttingPayrollController::class, 'index'])->name('index');
            Route::get('/create', [CuttingPayrollController::class, 'create'])->name('create');
            Route::post('/', [CuttingPayrollController::class, 'store'])->name('store');
            Route::get('/{period}', [CuttingPayrollController::class, 'show'])->name('show');
            Route::post('/{period}/finalize', [CuttingPayrollController::class, 'finalize'])->name('finalize');
            Route::post('/{period}/regenerate', [CuttingPayrollController::class, 'regenerate'])->name('regenerate');

            // SLIP BORONGAN PER OPERATOR — CUTTING
            Route::get('/{period}/slip/{employee}', [CuttingPayrollController::class, 'slip'])
                ->name('slip');
        });

    /*
    |--------------------------------------------------------------------------
    | PIECE RATES (master tarif borongan)
    |--------------------------------------------------------------------------
     */
    Route::prefix('payroll/piece-rates')
        ->name('payroll.piece_rates.')
        ->group(function () {
            Route::get('/', [PieceRateController::class, 'index'])->name('index');
            Route::get('/create', [PieceRateController::class, 'create'])->name('create');
            Route::post('/', [PieceRateController::class, 'store'])->name('store');
            Route::get('/{pieceRate}/edit', [PieceRateController::class, 'edit'])->name('edit');
            Route::put('/{pieceRate}', [PieceRateController::class, 'update'])->name('update');
            Route::delete('/{pieceRate}', [PieceRateController::class, 'destroy'])->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | SEWING PAYROLL
    |--------------------------------------------------------------------------
     */
    Route::prefix('payroll/sewing')
        ->name('payroll.sewing.')
        ->group(function () {
            Route::get('/', [SewingPayrollController::class, 'index'])->name('index');
            Route::get('/create', [SewingPayrollController::class, 'create'])->name('create');
            Route::post('/', [SewingPayrollController::class, 'store'])->name('store');
            Route::get('/{period}', [SewingPayrollController::class, 'show'])->name('show');
            Route::post('/{period}/finalize', [SewingPayrollController::class, 'finalize'])->name('finalize');
            Route::post('/{period}/regenerate', [SewingPayrollController::class, 'regenerate'])->name('regenerate');

            // SLIP BORONGAN PER OPERATOR — SEWING
            Route::get('/{period}/slip/{employee}', [SewingPayrollController::class, 'slip'])
                ->name('slip');

            // SLIP SEMUA OPERATOR — SEWING
            Route::get('/{period}/slip-all', [SewingPayrollController::class, 'slipAll'])
                ->name('slip_all');
        });

    /*
    |--------------------------------------------------------------------------
    | PAYROLL REPORTS
    |--------------------------------------------------------------------------
     */
    Route::get('/payroll/reports/operators', [PayrollReportController::class, 'operatorSummary'])
        ->name('payroll.reports.operators');

    Route::get('/payroll/reports/operator-slips', [PayrollReportController::class, 'operatorSlips'])
        ->name('payroll.reports.operator_slips');

    Route::get('/payroll/reports/operators/{employee}/detail',
        [PayrollReportController::class, 'operatorDetail']
    )->name('payroll.reports.operator_detail');
});
