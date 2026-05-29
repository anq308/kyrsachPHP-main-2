<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->default(1)->after('is_available');
            $table->unsignedInteger('reserved_quantity')->default(0)->after('stock_quantity');
        });

        DB::table('motorcycles')
            ->where('is_available', false)
            ->update(['stock_quantity' => 0]);

        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('motorcycle_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'reserved_quantity']);
        });
    }
};
