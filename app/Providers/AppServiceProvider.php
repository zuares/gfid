<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piece_rates', function (Blueprint $table) {
            $table->id();

            // Modul produksi: 'cutting', nanti bisa 'sewing', 'finishing'
            $table->string('module')->index(); // fokus awal: 'cutting'

            // Operator (Employee)
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            // Rate per kategori item (T-SHIRT, HOODIE, dll)
            $table->foreignId('item_category_id')
                ->nullable()
                ->constrained('item_categories')
                ->nullOnDelete();

            // Optional: override khusus per item tertentu
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // Tarif borongan per pcs (Rp/pcs)
            $table->decimal('rate_per_pcs', 12, 2)->default(0);

            // Masa berlaku
            $table->date('effective_from');
            $table->date('effective_to')->nullable(); // null = masih aktif

            $table->text('notes')->nullable();

            $table->timestamps();

            // Kombinasi unik untuk mencegah double rule
            $table->unique(
                ['module', 'employee_id', 'item_category_id', 'item_id', 'effective_from'],
                'uniq_piece_rates_rule'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_rates');
    }
};
