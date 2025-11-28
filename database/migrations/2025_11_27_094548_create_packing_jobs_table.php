<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_jobs', function (Blueprint $table) {
            $table->id();

            // Kode dokumen: PCK-YYYYMMDD-###
            $table->string('code')->unique();

            // Tanggal dokumen
            $table->date('date');

            // Status workflow
            $table->string('status')->default('draft'); // draft / posted
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('unposted_at')->nullable();

            // Info penjualan (opsional)
            $table->string('channel')->nullable(); // SHOPEE / TOKO / WEBSITE / dll
            $table->string('reference')->nullable(); // SO-xxx / DO-xxx / dsb

            // Catatan umum
            $table->text('notes')->nullable();

            // GUDANG ASAL & TUJUAN
            // Misal: FROM = FG, TO = PCK
            $table->foreignId('warehouse_from_id')
                ->constrained('warehouses');

            $table->foreignId('warehouse_to_id')
                ->constrained('warehouses');

            // Audit user
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_jobs');
    }
};
