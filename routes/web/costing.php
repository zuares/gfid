<?php
use App\Http\Controllers\Costing\HppController;
use App\Http\Controllers\Costing\ProductionCostPeriodController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('costing/hpp', [HppController::class, 'index'])
        ->name('costing.hpp.index');

    Route::post('costing/hpp/generate', [HppController::class, 'generate'])
        ->name('costing.hpp.generate');

    Route::post('costing/hpp/{snapshot}/set-active', [HppController::class, 'setActive'])
        ->name('costing.hpp.set_active');
});

// routes/web.php
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('costing')->name('costing.')->group(function () {

        // Resource: index, show, edit, update (kalau cuma itu yang dipakai)
        Route::resource('production-cost-periods', ProductionCostPeriodController::class)
            ->parameters([
                'production-cost-periods' => 'period', // biar {period} ↔ model ProductionCostPeriod
            ])
            ->names('production_cost_periods') // 👈 hasilnya: costing.production_cost_periods.index, dst.
            ->only(['index', 'show', 'edit', 'update']);

        // Tombol "Generate HPP dari payroll" untuk 1 periode
        Route::post('production-cost-periods/{period}/generate',
            [ProductionCostPeriodController::class, 'generate']
        )->name('production_cost_periods.generate');
    });
});
