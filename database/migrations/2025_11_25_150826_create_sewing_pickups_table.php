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
        Schema::create('sewing_pickups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // SWP-20251125-001
            $table->date('date');
            $table->foreignId('warehouse_id')->constrained(); // biasanya WIP-SEW atau CUT
            $table->foreignId('operator_id')->constrained('employees'); // operator jahit
            $table->string('status')->default('draft'); // draft, posted, closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sewing_pickups');
    }
};
