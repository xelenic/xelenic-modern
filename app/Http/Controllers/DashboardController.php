<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The signed-in user's own dashboard. Site-wide numbers live in the
     * admin panel (Admin\DashboardController).
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
            'projects' => $user->projects()->latest()->take(3)->get(),
        ]);
    }
}
