<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cover_download_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['serie', 'staff']);
            $table->string('url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cover_download_queue');
    }
};
