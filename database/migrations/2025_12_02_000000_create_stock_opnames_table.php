<?php

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // SO-YYYYMMDD-###
            $table->date('date');
            $table->foreignIdFor(Warehouse::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('status', ['draft', 'counting', 'reviewed', 'finalized'])
                ->default('draft');

            $table->text('notes')->nullable();

            // Tracking user
            $table->foreignIdFor(User::class, 'created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignIdFor(User::class, 'reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignIdFor(User::class, 'finalized_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();
        });

        Schema::create('stock_opname_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_opname_id')
                ->constrained('stock_opnames')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Snapshot qty dari sistem pada saat mulai opname
            $table->decimal('system_qty', 15, 3)->default(0);

            // Qty hasil hitung fisik
            $table->decimal('physical_qty', 15, 3)->nullable();

            // physical - system → bisa minus/plus
            $table->decimal('difference_qty', 15, 3)->default(0);

            $table->boolean('is_counted')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_lines');
        Schema::dropIfExists('stock_opnames');
    }
};
