<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // PLG, MRF, YYN, DLL
            $table->string('name');

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            // tipe vendor:
            // supplier → membeli bahan baku
            // cutting_vendor → makloon cutting
            // sewing_vendor → makloon sewing
            $table->string('type')->default('supplier');

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index(['type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
