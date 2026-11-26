<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A client story page's gallery: any number of photos, uploaded videos and
 * YouTube or Vimeo links, each with an optional caption, in order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            // image, video (an uploaded file) or link (YouTube or Vimeo).
            $table->string('kind', 10);
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('caption', 200)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['client_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_media');
    }
};
