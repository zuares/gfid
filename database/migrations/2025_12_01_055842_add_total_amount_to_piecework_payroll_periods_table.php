<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('piecework_payroll_periods', function (Blueprint $table) {
            // total_amount: total nominal gaji di periode tersebut
            $table->decimal('total_amount', 15, 2)
                ->default(0)
                ->after('status'); // sesuaikan posisi kalau mau
        });
    }

    public function down(): void
    {
        Schema::table('piecework_payroll_periods', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
};
