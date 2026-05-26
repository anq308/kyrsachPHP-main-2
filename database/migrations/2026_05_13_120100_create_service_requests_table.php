<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('motorcycle_model');
            $table->string('service_type');
            $table->date('preferred_date')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
