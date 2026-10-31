<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

class ClientStoryController extends Controller
{
    /**
     * A client's story page, opened from their logo on the home page.
     */
    public function show(Client $client): View
    {
        abort_unless($client->is_published, 404);

        return view('clients.show', [
            'client' => $client,
            'others' => Client::published()->ordered()->whereKeyNot($client->getKey())->get()->filter->hasStory()->take(6),
        ]);
    }
}
