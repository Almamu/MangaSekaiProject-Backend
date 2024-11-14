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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->binary('image')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('serie_staff', function (Blueprint $table) {
            $table->foreignId('serie_id')->constrained();
            $table->foreignId('staff_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};