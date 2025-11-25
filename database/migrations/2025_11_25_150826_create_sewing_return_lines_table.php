<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_return_lines', function (Blueprint $table) {
            $table->id();

            // Header Sewing Return
            $table->foreignId('sewing_return_id')
                ->constrained('sewing_returns')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Baris pickup hasil jahit yang dikembalikan
            $table->foreignId('sewing_pickup_line_id')
                ->constrained('sewing_pickup_lines')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Qty hasil jahit
            $table->decimal('qty_ok', 12, 2)->default(0);
            $table->decimal('qty_reject', 12, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_return_lines');
    }
};
