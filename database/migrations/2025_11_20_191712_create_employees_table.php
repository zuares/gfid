<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Kode karyawan dipakai juga sebagai employee_code untuk login
            $table->string('code')->unique(); // misal: EMP001
            $table->string('name');

            // Role operasional: sewing, cutting, operating, admin, owner
            $table->enum('role', ['sewing', 'cutting', 'operating', 'admin', 'owner', 'other'])
                ->default('other');

            // Jenis gaji: variable (borongan) atau fixed (gaji tetap)
            $table->enum('payment_type', ['variable', 'fixed'])->default('variable');

            // Gaji mingguan tetap (untuk payment_type = fixed)
            $table->decimal('weekly_fixed_salary', 15, 2)->default(0); // Rp

            // Rate default per pcs (untuk payment_type = variable), opsional
            $table->decimal('default_piece_rate', 15, 2)->default(0);

            $table->boolean('active')->default(true);

            $table->string('phone')->nullable();
            $table->string('address')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
