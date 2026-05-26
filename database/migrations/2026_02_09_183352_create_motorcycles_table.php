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
        Schema::create('motorcycles', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('type'); // cross, sport, chopper, etc.
            $table->integer('year');
            $table->integer('engine_capacity'); // cc
            $table->integer('power'); // hp
            $table->integer('price');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motorcycles');
    }
};
