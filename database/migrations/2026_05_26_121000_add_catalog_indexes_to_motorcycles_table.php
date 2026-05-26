<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->index('brand');
            $table->index('type');
            $table->index('year');
            $table->index('price');
            $table->index('engine_capacity');
            $table->index('power');
            $table->index('is_available');
            $table->index('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('motorcycles', function (Blueprint $table) {
            $table->dropIndex(['brand']);
            $table->dropIndex(['type']);
            $table->dropIndex(['year']);
            $table->dropIndex(['price']);
            $table->dropIndex(['engine_capacity']);
            $table->dropIndex(['power']);
            $table->dropIndex(['is_available']);
            $table->dropIndex(['views_count']);
        });
    }
};
