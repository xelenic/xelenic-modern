<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // the template's id in Kiosk Studio
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->string('kind', 20); // builtin, preset or custom
            $table->string('extends', 20)->nullable(); // the built-in game a preset is based on
            $table->string('version', 40)->default('1.0.0');
            $table->string('author')->nullable();
            $table->string('icon', 16)->nullable(); // an emoji
            $table->text('icon_image')->nullable(); // or a small picture from the package, as a data URL
            $table->string('color', 9)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Kiosk Studio's own games, so the marketplace has them from the start.
        $now = now();
        DB::table('game_templates')->insert(collect([
            ['quiz', 'Quiz', 'Questions with timed answers', '🧠', '#4b1d8f'],
            ['catch', 'Catch', 'Drag to catch falling items', '🧺', '#0f6b55'],
            ['slice', 'Slice', 'Swipe to slice flying fruit', '🍉', '#7a2a1a'],
            ['wheel', 'Spin Wheel', 'Spin the wheel to win a prize', '🎡', '#8a5a00'],
        ])->map(fn (array $game, int $i) => [
            'slug' => $game[0],
            'name' => $game[1],
            'description' => $game[2],
            'kind' => 'builtin',
            'icon' => $game[3],
            'color' => $game[4],
            'author' => 'Kiosk Studio',
            'is_active' => true,
            'sort_order' => $i + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_templates');
    }
};
