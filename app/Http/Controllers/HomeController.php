<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The marketing home page, with the published client logos.
     */
    public function __invoke(): View
    {
        return view('welcome', [
            'clients' => Client::published()->ordered()->get(),
        ]);
    }
}
