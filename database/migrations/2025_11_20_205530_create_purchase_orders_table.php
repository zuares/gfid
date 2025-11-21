<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // PO-YYYYMMDD-###
            $table->date('date');

            $table->foreignId('supplier_id')->constrained()->cascadeOnUpdate();

            // Semua nilai uang / qty pakai DECIMAL(18,2) → cocok untuk format Indo
            $table->decimal('subtotal', 18, 2)->default(0); // total sebelum diskon & pajak
            $table->decimal('discount', 18, 2)->default(0); // diskon total invoice (Rupiah)
            $table->decimal('tax_percent', 5, 2)->default(0); // contoh: 11.00 untuk 11%
            $table->decimal('tax_amount', 18, 2)->default(0); // nilai PPN (Rupiah)
            $table->decimal('shipping_cost', 18, 2)->default(0); // ongkir
            $table->decimal('grand_total', 18, 2)->default(0); // total akhir (rupiah)

            // status: draft / submitted / received / cancelled
            $table->string('status', 20)->default('draft');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            // Index tambahan untuk filter cepat
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
