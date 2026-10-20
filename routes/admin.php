<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin,trainer'])->prefix('panel')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Teams & Players
    Route::get('/teams', function () {
        return view('admin.teams.index');
    })->name('teams.index');
    Route::get('/players', function () {
        return view('admin.players.index');
    })->name('players.index');

    // Games
    Route::get('/games', function () {
        return view('admin.games.index');
    })->name('games.index');

    // Turnieje i sparingi w trakcie sezonu
    Route::view('/tournaments', 'admin.tournaments.index')->name('tournaments.index');

    // Competitions & Standings
    Route::get('/competitions', function () {
        return view('admin.competitions.index');
    })->name('competitions.index');
    Route::get('/competition-levels', function () {
        return view('admin.competition-levels.index');
    })->name('competition-levels.index');
    Route::get('/standings', function () {
        return view('admin.standings.index');
    })->name('standings.index');

    // Seasons & Rival teams
    Route::get('/seasons', function () {
        return view('admin.seasons.index');
    })->name('seasons.index');
    Route::get('/rival-teams', function () {
        return view('admin.rival-teams.index');
    })->name('rival-teams.index');

    // Articles
    Route::get('/articles', function () {
        return view('admin.articles.index');
    })->name('articles.index');

    // Hero slider
    Route::get('/hero-slides', function () {
        return view('admin.hero-slides.index');
    })->name('hero-slides.index');

    // Documents
    Route::get('/documents', function () {
        return view('admin.documents.index');
    })->name('documents.index');

    // Sponsors
    Route::get('/sponsors', function () {
        return view('admin.sponsors.index');
    })->name('sponsors.index');

    // Users
    Route::get('/users', function () {
        return view('admin.users.index');
    })->name('users.index');

    // Site settings & pages
    Route::middleware('role:admin')->group(function () {
        Route::view('/settings', 'admin.settings.index')->name('settings.index');
        Route::view('/pages', 'admin.pages.index')->name('pages.index');
        Route::view('/statistics', 'admin.statistics.index')->name('statistics.index');
    });

    // Zdjęcia i dane proponowane przez zawodników i rodziców
    Route::view('/player-change-requests', 'admin.player-change-requests.index')->name('player-change-requests.index');

    // Profile change requests
    Route::get('/profile-change-requests', function () {
        return view('admin.profile-change-requests.index');
    })->name('profile-change-requests.index');
});

// Stary adres panelu. Na XAMPP /admin bywa zajęty przez narzędzie bazy danych, dlatego panel jest pod /panel.
Route::redirect('/admin', '/panel', 301);
Route::get('/admin/{path}', fn (string $path) => redirect('/panel/'.$path, 301))->where('path', '.*');
