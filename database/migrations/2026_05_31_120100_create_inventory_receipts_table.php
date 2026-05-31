<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motorcycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_cost')->default(0);
            $table->string('status')->default('planned');
            $table->date('expected_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['status', 'expected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receipts');
    }
};
