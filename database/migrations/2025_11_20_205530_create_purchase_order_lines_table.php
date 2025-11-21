<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate();

            // jika 1 PO line langsung terhubung ke LOT tertentu (kain roll)
            $table->foreignId('lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // qty & harga pakai decimal(18,2) → aman untuk rupiah & koma 2 digit
            $table->decimal('qty', 18, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0); // harga per unit (rupiah)
            $table->decimal('discount', 18, 2)->default(0); // diskon per line (rupiah)
            $table->decimal('line_total', 18, 2)->default(0); // (qty * unit_price) - discount

            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->index(['purchase_order_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
