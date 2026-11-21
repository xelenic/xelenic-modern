<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each game in Kiosk Studio's marketplace gets a store page: a cover photo,
 * screenshots, a longer write-up, and star reviews from signed-in users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_templates', function (Blueprint $table) {
            $table->text('details')->nullable()->after('description');
            $table->string('thumbnail_path')->nullable()->after('color');
            $table->json('screenshots')->nullable()->after('thumbnail_path');
        });

        Schema::create('game_template_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            // One review per person per game; posting again updates it.
            $table->unique(['game_template_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_template_reviews');

        Schema::table('game_templates', function (Blueprint $table) {
            $table->dropColumn(['details', 'thumbnail_path', 'screenshots']);
        });
    }
};
