<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piecework_payroll_periods', function (Blueprint $table) {
            $table->id();

            // Modul: 'cutting' untuk kasus ini
            $table->string('module')->index();

            $table->date('period_start');
            $table->date('period_end');

            // draft = masih bisa di-generate ulang
            // posted = sudah final, tidak diubah
            $table->string('status')->default('draft');

            $table->text('notes')->nullable();

            // Siapa yang bikin & posting
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piecework_payroll_periods');
    }
};
