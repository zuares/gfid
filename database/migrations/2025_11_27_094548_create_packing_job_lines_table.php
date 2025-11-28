<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_job_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('packing_job_id')
                ->constrained('packing_jobs')
                ->onDelete('cascade');

            $table->foreignId('item_id')
                ->constrained('items');

            // Snapshot qty FG (opsional, lebih ke info)
            $table->decimal('qty_fg', 15, 2)->default(0);

            // Qty yang benar-benar dipacking
            $table->decimal('qty_packed', 15, 2);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_job_lines');
    }
};
