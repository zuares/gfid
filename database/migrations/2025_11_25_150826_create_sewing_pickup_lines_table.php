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
        Schema::create('sewing_pickup_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewing_pickup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cutting_job_bundle_id')->constrained(); // bundle dari cutting
            $table->foreignId('finished_item_id')->constrained('items'); // mirror dari bundle
            $table->decimal('qty_bundle', 10, 2); // qty panel yang diambil (pcs)
            $table->decimal('qty_returned_ok', 10, 2)->default(0); // diisi saat return
            $table->decimal('qty_returned_reject', 10, 2)->default(0); // diisi saat return
            $table->string('status')->default('in_progress'); // in_progress, done
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sewing_pickup_lines');
    }
};
