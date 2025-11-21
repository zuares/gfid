<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // FLC, RIB, ACC, BENANG
            $table->string('name'); // Fleece 280 GSM Black

            // Optional: kategori bisa aktif atau tidak
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};
