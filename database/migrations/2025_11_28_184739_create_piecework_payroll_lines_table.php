<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piecework_payroll_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_period_id')
                ->constrained('piecework_payroll_periods')
                ->cascadeOnDelete();

            // Operator (tukang potong)
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            // Kategori & item yang dihitung
            $table->foreignId('item_category_id')
                ->nullable()
                ->constrained('item_categories')
                ->nullOnDelete();

            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // Total qty OK (pcs) di periode itu untuk kombinasi di atas
            // (boleh integer, tapi bisa juga fractional → pakai decimal)
            $table->decimal('total_qty_ok', 12, 2)->default(0);

            // Rate per pcs yang dipakai saat posting (freeze dari piece_rates)
            $table->decimal('rate_per_pcs', 12, 2)->default(0);

            // Total gaji = total_qty_ok * rate_per_pcs
            $table->decimal('amount', 14, 2)->default(0);

            $table->timestamps();

            // Index untuk query report cepat
            $table->index(['employee_id', 'item_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piecework_payroll_lines');
    }
};
