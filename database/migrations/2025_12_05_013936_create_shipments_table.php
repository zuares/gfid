<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // SHP-YYYYMMDD-###

            $table->date('date');

            // optional link ke invoice (untuk sekarang fokus dari Sales Invoice)
            $table->foreignId('sales_invoice_id')
                ->nullable()
                ->constrained('sales_invoices')
                ->nullOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('status')->default('draft'); // draft, packed, shipped, delivered

            $table->string('shipping_method')->nullable(); // JNE, J&T, Kurir, dll
            $table->string('tracking_no')->nullable();

            $table->text('shipping_address')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
