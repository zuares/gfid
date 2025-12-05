<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->integer('qty_scanned');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'item_id']); // 1 item 1 baris per shipment
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_lines');
    }
};
