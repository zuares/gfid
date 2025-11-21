<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // harga terakhir dalam rupiah (tanpa format titik/koma saat disimpan)
            $table->decimal('last_price', 18, 2)->default(0);

            $table->timestamps();

            // 1 supplier + 1 item → 1 baris
            $table->unique(['supplier_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_prices');
    }
};
