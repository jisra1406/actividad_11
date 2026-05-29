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
        Schema::create('bovines', function (Blueprint $table) {
            $table->id();
            $table->string('tag_number')->unique();
            $table->string('breed');
            $table->float('estimated_weight');
            $table->timestamp('calculated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bovines');
    }
};
