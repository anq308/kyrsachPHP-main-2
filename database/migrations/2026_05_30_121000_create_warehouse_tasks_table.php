<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('motorcycle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('new')->index();
            $table->text('comment')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['motorcycle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_tasks');
    }
};
