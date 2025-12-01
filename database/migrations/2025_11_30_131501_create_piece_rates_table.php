<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('piece_rates', function (Blueprint $table) {
            $table->id();

            // Modul: cutting / sewing / finishing / dll.
            $table->string('module', 50);

            // Karyawan yang menerima tariff
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            // Bisa pakai item kategori ATAU item spesifik
            $table->foreignId('item_category_id')
                ->nullable()
                ->constrained('item_categories')
                ->nullOnDelete();

            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // Tarif per pcs
            $table->decimal('rate_per_pcs', 12, 2)->default(0);

            // Masa berlaku tarif
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            // Catatan opsional
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('piece_rates');
    }
};
