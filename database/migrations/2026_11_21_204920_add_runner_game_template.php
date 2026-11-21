<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kiosk Studio's fifth built-in game, Runner (runner() in
 * public/nignest/js/games.js), offered in its game templates marketplace.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('game_templates')->where('slug', 'runner')->exists()) {
            return;
        }

        DB::table('game_templates')->insert([
            'slug' => 'runner',
            'name' => 'Runner',
            'description' => 'Tap to jump obstacles and grab coins',
            'kind' => 'builtin',
            'icon' => '🏃',
            'color' => '#0b5f86',
            'author' => 'Kiosk Studio',
            'is_active' => true,
            'sort_order' => (int) DB::table('game_templates')->where('kind', 'builtin')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('game_templates')->where('slug', 'runner')->where('kind', 'builtin')->delete();
    }
};
