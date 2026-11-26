<?php

use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\CloudController as AdminCloudController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GameTemplateController as AdminGameTemplateController;
use App\Http\Controllers\Admin\JobApplicationController as AdminJobApplicationController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AnimationMarketplaceController;
use App\Http\Controllers\CareersController;
use App\Http\Controllers\ClientStoryController;
use App\Http\Controllers\CloudController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameMarketplaceController;
use App\Http\Controllers\GameTemplateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KioskStudioPageController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TrendingKioskAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// A client's story, opened from their logo on the home page.
Route::get('clients', [ClientStoryController::class, 'index'])->name('clients.index');
Route::get('clients/{client:slug}', [ClientStoryController::class, 'show'])->name('clients.show');

// A kiosk app shared from its project page: anyone with the link can play it.
Route::get('play/{token}', PlayController::class)
    ->where('token', '[A-Za-z0-9]{40}')
    ->middleware('throttle:60,1')
    ->name('play');

// The game templates marketplace on the website: every game, its store page
// with a live demo, and reviews from signed-in users.
Route::controller(GameMarketplaceController::class)->prefix('marketplace/games')->name('marketplace.games.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{gameTemplate:slug}', 'show')->name('show');
    Route::get('{gameTemplate:slug}/demo', 'demo')->middleware('throttle:60,1')->name('demo');

    Route::middleware('auth')->group(function () {
        Route::get('{gameTemplate:slug}/review', 'startReview')->name('review.start');
        Route::put('{gameTemplate:slug}/review', 'review')->middleware('throttle:20,1')->name('review');
        Route::delete('{gameTemplate:slug}/review', 'destroyReview')->middleware('throttle:20,1')->name('review.destroy');
    });
});

// About Xelenic Kiosk Studio, with screenshots of it.
Route::get('kiosk-studio', KioskStudioPageController::class)->name('studio');

// Animation templates: Kiosk Studio's animated screens, playing live.
Route::controller(AnimationMarketplaceController::class)->prefix('marketplace/animations')->name('marketplace.animations.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('show');
});

// Trending kiosk apps: concept campaigns for brands and events, playable.
Route::controller(TrendingKioskAppController::class)->prefix('trending-kiosk-apps')->name('trending.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('show');
    Route::get('{slug}/demo', 'demo')->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('demo');
});

// Developer careers: the roles, life on the team, and applying.
Route::get('careers', [CareersController::class, 'index'])->name('careers');
Route::post('careers/apply', [CareersController::class, 'apply'])->middleware('throttle:5,1')->name('careers.apply');

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

    // Cloud: VPS, web hosting, domains and dedicated servers resold from
    // InterServer (App\Cloud). Orders wait for the team's approval.
    Route::controller(CloudController::class)->prefix('cloud')->name('cloud.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('domains/check', 'checkDomain')->middleware('throttle:30,1')->name('domains.check');
        Route::get('order/{product}', 'create')->name('create');
        Route::post('order/{product}', 'store')->middleware('throttle:10,1')->name('store');
        Route::get('{cloudService}', 'show')->name('show');
        Route::post('{cloudService}/refresh', 'refresh')->middleware('throttle:20,1')->name('refresh');
        Route::post('{cloudService}/cancel', 'cancel')->name('cancel');
        Route::post('{cloudService}/actions/{action}', 'action')->middleware('throttle:20,1')->name('action');
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

        Route::controller(AdminJobApplicationController::class)->prefix('careers')->name('careers.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{jobApplication}', 'show')->name('show');
            Route::patch('{jobApplication}', 'update')->name('update');
            Route::get('{jobApplication}/cv', 'cv')->name('cv');
            Route::delete('{jobApplication}', 'destroy')->name('destroy');
        });

        Route::post('clients/preview-article', [AdminClientController::class, 'previewArticle'])->name('clients.preview-article');
        Route::resource('clients', AdminClientController::class)->except('show');

        Route::controller(AdminCloudController::class)->prefix('cloud')->name('cloud.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('settings', 'settings')->name('settings');
            Route::put('settings', 'updateSettings')->name('settings.update');
            Route::get('{cloudService}', 'show')->name('show');
            Route::post('{cloudService}/approve', 'approve')->name('approve');
            Route::post('{cloudService}/reject', 'reject')->name('reject');
            Route::post('{cloudService}/retry', 'retry')->name('retry');
            Route::post('{cloudService}/sync', 'sync')->name('sync');
            Route::post('{cloudService}/cancel', 'cancel')->name('cancel');
        });
        Route::resource('game-templates', AdminGameTemplateController::class)->except('show');
        Route::delete('game-templates/{game_template}/reviews/{review}', [AdminGameTemplateController::class, 'destroyReview'])->name('game-templates.reviews.destroy');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('roles', RoleController::class)->except('show');
    });
});
