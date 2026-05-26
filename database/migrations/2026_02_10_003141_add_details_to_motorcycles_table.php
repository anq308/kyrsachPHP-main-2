<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->string('transmission')->nullable()->after('is_available');
            $table->string('cooling')->nullable()->after('transmission');
            $table->string('fuel_system')->nullable()->after('cooling');
            $table->integer('weight')->nullable()->after('fuel_system');
            $table->decimal('tank_capacity', 8, 2)->nullable()->after('weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropColumn(['transmission', 'cooling', 'fuel_system', 'weight', 'tank_capacity']);
        });
    }
};
