<?php

use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GameTemplateController as AdminGameTemplateController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ClientStoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameTemplateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// A client's story, opened from their logo on the home page.
Route::get('clients/{client:slug}', [ClientStoryController::class, 'show'])->name('clients.show');

// A kiosk app shared from its project page: anyone with the link can play it.
Route::get('play/{token}', PlayController::class)
    ->where('token', '[A-Za-z0-9]{40}')
    ->middleware('throttle:60,1')
    ->name('play');

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
        Route::get('{project}/kiosk-design', 'kioskDesign')->name('kiosk-design');
        Route::put('{project}/kiosk-design', 'updateKioskDesign')->name('kiosk-design.update');
        Route::get('{project}/kiosk-data', 'kioskData')->name('kiosk-data');
        Route::post('{project}/play-link', 'sharePlay')->name('play-link');
        Route::delete('{project}/play-link', 'stopSharingPlay')->name('play-link.destroy');
        Route::post('{project}/cancel', 'cancel')->name('cancel');
    });

    // Kiosk Studio's game templates marketplace (public/nignest) lists these,
    // and downloads an uploaded game's .zip when it's added to a design.
    // Each game's store page there, where signed-in users rate and review it.
    Route::controller(GameTemplateController::class)->prefix('game-templates')->name('game-templates.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{gameTemplate:slug}', 'show')->name('show');
        Route::get('{gameTemplate:slug}/download', 'download')->name('download');
        Route::put('{gameTemplate:slug}/review', 'review')->middleware('throttle:20,1')->name('review');
        Route::delete('{gameTemplate:slug}/review', 'destroyReview')->middleware('throttle:20,1')->name('review.destroy');
    });

    // Admin panel: open to anyone with an admin permission (the
    // "access-admin" gate); each controller checks its own permissions.
    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::controller(AdminProjectController::class)->prefix('projects')->name('projects.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{project}', 'show')->name('show');
            Route::get('{project}/kiosk-design', 'kioskDesign')->name('kiosk-design');
            Route::get('{project}/kiosk-data', 'kioskData')->name('kiosk-data');
            Route::patch('{project}/status', 'updateStatus')->name('status');
        });

        Route::resource('clients', AdminClientController::class)->except('show');
        Route::resource('game-templates', AdminGameTemplateController::class)->except('show');
        Route::delete('game-templates/{game_template}/reviews/{review}', [AdminGameTemplateController::class, 'destroyReview'])->name('game-templates.reviews.destroy');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('roles', RoleController::class)->except('show');
    });
});
