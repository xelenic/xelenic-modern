<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Show the signed-in user's dashboard. People who can see other users
     * also get the site-wide numbers and the latest sign-ups.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $canViewUsers = $user->can('view users');

        return view('dashboard', [
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
            'stats' => $canViewUsers ? [
                'users' => User::count(),
                'newThisWeek' => User::where('created_at', '>=', now()->subWeek())->count(),
                'roles' => Role::count(),
            ] : null,
            'recentUsers' => $canViewUsers ? User::with('roles')->latest()->take(5)->get() : collect(),
            'projects' => $user->projects()->latest()->take(3)->get(),
        ]);
    }
}
