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
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('chapter_count')->default(0);
            $table->integer('pages_count')->default(0);
            $table->string('description');
            $table->boolean('synced')->default(false);
            $table->binary('image')->nullable();
            $table->string('mime_type')->nullable();
            $table->bigInteger('external_id')->nullable();
            $table->string('matcher')->nullable();
            $table->json('blocked_fields')->default(json_encode([]));
            $table->timestamps();
        });

        Schema::create('series_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('library_id');
            $table->string('basepath');
            $table->foreignId('serie_id')->nullable()->constrained()->cascadeOnDelete();

            $table->unique(['library_id', 'basepath']);
        });

        Schema::create('chapters_scans', function (Blueprint $table) {
            $table->id();
            $table->string('basepath');
            $table->foreignId('series_scan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->cascadeOnDelete();

            $table->unique(['basepath', 'series_scan_id']);
        });

        Schema::create('pages_scans', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->foreignId('chapters_scan_id')->constrained()->cascadeOnDelete();
            $table->string('mime_type');

            $table->unique(['path', 'chapters_scan_id']);
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->constrained()->cascadeOnDelete();
            $table->float('number');
            $table->integer('pages_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
