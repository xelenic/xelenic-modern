<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Login, sign-up, logout and password reset routes come from Fortify
// (config/fortify.php); their views are set in FortifyServiceProvider.

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::view('profile', 'profile.edit')->name('profile.edit');

    // Clients ask for projects; people who can "manage projects" see
    // everyone's and move them through their statuses.
    Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('create/{type}', 'createType')->name('create.type');
        Route::post('/', 'store')->name('store');
        Route::get('{project}', 'show')->name('show');
        Route::patch('{project}/status', 'updateStatus')->name('status')->middleware('permission:manage projects');
        Route::post('{project}/cancel', 'cancel')->name('cancel');
    });

    // Each controller checks its own Spatie permissions.
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::resource('roles', RoleController::class)->except('show');
    });
});
