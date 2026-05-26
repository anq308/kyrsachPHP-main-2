<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('cash_pickup')->after('status');
            $table->string('payment_status')->default('pending')->after('payment_method');
            $table->foreignId('pickup_point_id')->nullable()->after('payment_status')->constrained()->nullOnDelete();
            $table->timestamp('pickup_ready_at')->nullable()->after('pickup_point_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_point_id');
            $table->dropColumn(['payment_method', 'payment_status', 'pickup_ready_at']);
        });
    }
};
