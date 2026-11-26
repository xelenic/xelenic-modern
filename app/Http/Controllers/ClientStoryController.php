<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

class ClientStoryController extends Controller
{
    /**
     * Our clients: every published client's logo, their stories first.
     */
    public function index(): View
    {
        $clients = Client::published()->ordered()->withExists('media')->get();

        return view('clients.index', [
            'clients' => $clients,
            // Stories with a photo lead the page.
            'stories' => $clients->filter(fn (Client $client) => $client->hasStory())
                ->sortBy(fn (Client $client) => $client->image_path ? 0 : 1)
                ->values(),
        ]);
    }

    /**
     * A client's page, opened from their logo on the home page: their story
     * when one's been written, and other clients to look at next (those with
     * a story first).
     */
    public function show(Client $client): View
    {
        abort_unless($client->is_published, 404);

        return view('clients.show', [
            'client' => $client->load('media'),
            'others' => Client::published()->ordered()->withExists('media')->whereKeyNot($client->getKey())->get()
                ->sortBy(fn (Client $other) => $other->hasStory() ? 0 : 1)
                ->take(8)
                ->values(),
        ]);
    }
}
