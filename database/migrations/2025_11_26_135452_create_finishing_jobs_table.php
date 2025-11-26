<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finishing_jobs', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // FIN-YYYYMMDD-###
            $table->date('date')->index();

            $table->string('status', 20)->default('draft'); // draft / posted
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_jobs');
    }
};
