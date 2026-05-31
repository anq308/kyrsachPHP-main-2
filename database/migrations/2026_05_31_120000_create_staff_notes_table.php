<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('noteable');
            $table->text('body');
            $table->timestamps();

            $table->index(['noteable_type', 'noteable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notes');
    }
};
