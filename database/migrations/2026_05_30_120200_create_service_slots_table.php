<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_slots', function (Blueprint $table) {
            $table->id();
            $table->date('service_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('service_type')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('booked_count')->default(0);
            $table->string('status')->default('available')->index();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['service_date', 'status']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('service_slot_id')
                ->nullable()
                ->after('user_id')
                ->constrained('service_slots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_slot_id');
        });

        Schema::dropIfExists('service_slots');
    }
};
