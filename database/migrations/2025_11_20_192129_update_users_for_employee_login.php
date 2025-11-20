<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom untuk login
            $table->string('employee_code')->unique()->after('id');

            // Role di level user (mirror dari employee, tapi bisa beda kalau mau)
            $table->enum('role', ['sewing', 'cutting', 'operating', 'admin', 'owner', 'other'])
                ->default('other')
                ->after('employee_code');

            // Relasi opsional ke employees
            $table->unsignedBigInteger('employee_id')->nullable()->after('role');

            // Biar email tidak wajib
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'employee_code')) {
                $table->dropUnique(['employee_code']);
                $table->dropColumn('employee_code');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('users', 'employee_id')) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            }
        });
    }
};
