<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // FLC280BLK, K7BLK, BENANG-BLK
            $table->string('name'); // Nama item
            $table->string('unit')->default('pcs'); // meter, roll, pcs

            // material = bahan baku
            // finished = barang jadi
            // accessory = aksesoris
            $table->string('type')->default('material');

            $table->foreignId('item_category_id')
                ->nullable()
                ->after('code') // atau after kolom lain, sesuaikan
                ->constrained('item_categories')
                ->nullOnDelete();

            // HPP (moving average)
            $table->decimal('last_purchase_price', 18, 2)->default(0);
            $table->decimal('hpp', 18, 2)->default(0);

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
