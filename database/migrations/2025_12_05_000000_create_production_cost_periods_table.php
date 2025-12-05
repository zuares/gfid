<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_cost_periods', function (Blueprint $table) {
            $table->id();

            // Kode periode costing, mis: PCP-202512-001
            $table->string('code')->unique();

            // Nama/deskripsi periode, mis: "HPP Desember 2025"
            $table->string('name')->nullable();

            // Range tanggal yang dicakup (biasanya ikut payroll period)
            $table->date('date_from');
            $table->date('date_to');

            // Tanggal snapshot HPP (biasanya = date_to)
            $table->date('snapshot_date');

            // Link ke payroll period (opsional, supaya bisa trace sourcing biaya)
            $table->unsignedBigInteger('cutting_payroll_period_id')->nullable();
            $table->unsignedBigInteger('sewing_payroll_period_id')->nullable();
            $table->unsignedBigInteger('finishing_payroll_period_id')->nullable();

            // Status periode costing:
            // draft  = baru disiapkan, belum generate HPP
            // ready  = sudah cek angka, siap generate snapshot
            // posted = HPP sudah di-generate ke item_cost_snapshots
            $table->string('status')->default('draft');

            // Penanda global: ini periode costing aktif untuk penentuan HPP default
            $table->boolean('is_active')->default(false);

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Foreign key opsional (kalau mau strict, sesuaikan nama tabel user/payroll kamu)
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('cutting_payroll_period_id')->references('id')->on('piecework_payroll_periods')->nullOnDelete();
            // $table->foreign('sewing_payroll_period_id')->references('id')->on('piecework_payroll_periods')->nullOnDelete();
            // $table->foreign('finishing_payroll_period_id')->references('id')->on('piecework_payroll_periods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_cost_periods');
    }
};
