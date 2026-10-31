<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * The logos the home page started with, from the original design in
     * public/moderning_xelenic: name, file, and whether the logo is
     * light-on-dark (it then gets a navy tile).
     */
    private const CLIENTS = [
        ['Anchor', 'anchor'],
        ['Nescafé', 'nescafe'],
        ['Haleon', 'haleon'],
        ['Mirissa Waves', 'mirissa-waves'],
        ['Snickers', 'snickers'],
        ['MindSpark', 'mindspark', true],
        ['Fonterra', 'fonterra'],
        ['Wasthi FZ LLE', 'wasthi'],
        ['Redmi', 'redmi'],
        ['Milan', 'milan'],
        ['Sensodyne', 'sensodyne'],
        ['Zeebroo', 'zeebroo'],
        ['Panadol', 'panadol'],
        ['Heavenly', 'heavenly'],
        ["M&M's", 'mms'],
        ['Ratthi', 'ratthi'],
    ];

    /**
     * Add any of the original clients that aren't there yet. Safe to run
     * again: existing clients (and edits made to them) are left alone.
     */
    public function run(): void
    {
        $disk = Storage::disk('public');

        foreach (self::CLIENTS as $order => $client) {
            [$name, $file, $dark] = $client + [2 => false];
            $slug = Str::slug($name);
            $path = "clients/{$slug}/{$file}.webp";

            if (Client::where('slug', $slug)->exists()) {
                continue;
            }

            if (! $disk->exists($path)) {
                $disk->put($path, file_get_contents(public_path("moderning_xelenic/assets/img/clients-web/{$file}.webp")));
            }

            Client::create([
                'name' => $name,
                'slug' => $slug,
                'logo_path' => $path,
                'dark_logo' => $dark,
                'is_published' => true,
                'sort_order' => $order + 1,
            ]);
        }
    }
}
