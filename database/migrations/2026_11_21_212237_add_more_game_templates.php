<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Four more of Kiosk Studio's built-in games (public/nignest/js/games.js),
 * offered in its game templates marketplace after the others.
 */
return new class extends Migration
{
    /**
     * [slug, name, description, icon, colour]
     */
    private const GAMES = [
        ['whack', 'Whack Attack', 'Tap the critters as they pop up', '🔨', '#2d6a4f'],
        ['stack', 'Tower Stack', 'Tap to drop blocks and build high', '🧱', '#1d3557'],
        ['scratch', 'Scratch & Win', 'Scratch the card to reveal a prize', '🎟️', '#a4133c'],
        ['hoop', 'Hoop Shot', 'Swipe up to shoot baskets', '🏀', '#c2410c'],
    ];

    public function up(): void
    {
        $order = (int) DB::table('game_templates')->where('kind', 'builtin')->max('sort_order');

        foreach (self::GAMES as [$slug, $name, $description, $icon, $color]) {
            if (DB::table('game_templates')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('game_templates')->insert([
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'kind' => 'builtin',
                'icon' => $icon,
                'color' => $color,
                'author' => 'Kiosk Studio',
                'is_active' => true,
                'sort_order' => ++$order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('game_templates')->whereIn('slug', array_column(self::GAMES, 0))->where('kind', 'builtin')->delete();
    }
};
