<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_returns', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');

            // Gudang sumber hasil jahit (WIP-SEW / gudang sewing)
            $table->foreignId('warehouse_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Operator jahit
            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // draft / posted / closed
            $table->string('status')->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_returns');
    }
};
