<?php

use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ClientStoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// A client's story, opened from their logo on the home page.
Route::get('clients/{client:slug}', [ClientStoryController::class, 'show'])->name('clients.show');

// Login, sign-up, logout and password reset routes come from Fortify
// (config/fortify.php); their views are set in FortifyServiceProvider.

Route::middleware('auth')->group(function () {
    // Client panel
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::view('profile', 'profile.edit')->name('profile.edit');

    Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::get('create/{type}', 'createType')->name('create.type');
        Route::post('/', 'store')->name('store');
        Route::get('{project}', 'show')->name('show');
        Route::post('{project}/cancel', 'cancel')->name('cancel');
    });

    // Admin panel: open to anyone with an admin permission (the
    // "access-admin" gate); each controller checks its own permissions.
    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::controller(AdminProjectController::class)->prefix('projects')->name('projects.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{project}', 'show')->name('show');
            Route::patch('{project}/status', 'updateStatus')->name('status');
        });

        Route::resource('clients', AdminClientController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('roles', RoleController::class)->except('show');
    });
});
