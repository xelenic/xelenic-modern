<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kiosk Studio's sixth built-in game, Memory Match (memory() in
 * public/nignest/js/games.js), offered in its game templates marketplace.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('game_templates')->where('slug', 'memory')->exists()) {
            return;
        }

        DB::table('game_templates')->insert([
            'slug' => 'memory',
            'name' => 'Memory Match',
            'description' => 'Flip the cards to find matching pairs',
            'kind' => 'builtin',
            'icon' => '🃏',
            'color' => '#9d174d',
            'author' => 'Kiosk Studio',
            'is_active' => true,
            'sort_order' => (int) DB::table('game_templates')->where('kind', 'builtin')->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('game_templates')->where('slug', 'memory')->where('kind', 'builtin')->delete();
    }
};
