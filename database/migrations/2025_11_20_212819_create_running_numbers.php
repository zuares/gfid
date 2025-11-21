<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('running_numbers', function (Blueprint $table) {
            $table->id();

            $table->string('prefix', 20); // PO, INV, LOT, TRF, dll
            $table->date('date'); // tanggal (2025-11-21)
            $table->unsignedInteger('last_number')->default(0);

            $table->timestamps();

            $table->unique(['prefix', 'date']); // 1 baris per prefix per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('running_numbers');
    }
};
