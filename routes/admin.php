<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Tournament;
use App\Support\DataImport;
use Illuminate\Support\Facades\Route;

// Każda sekcja panelu wymaga swojego uprawnienia (App\Permission). Uprawnienia ról
// zmienia się w panelu: Role i uprawnienia.
Route::middleware(['auth', 'can:panel'])->prefix('panel')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('can:teams.manage')->group(function () {
        Route::view('/teams', 'admin.teams.index')->name('teams.index');
        Route::view('/players', 'admin.players.index')->name('players.index');
    });

    Route::middleware('can:games.manage')->group(function () {
        Route::view('/games', 'admin.games.index')->name('games.index');
        Route::view('/standings', 'admin.standings.index')->name('standings.index');
    });

    // Turnieje i sparingi w trakcie sezonu
    Route::middleware('can:tournaments.manage')->group(function () {
        Route::view('/tournaments', 'admin.tournaments.index')->name('tournaments.index');
        Route::get('/tournaments/{tournament}', fn (Tournament $tournament) => view('admin.tournaments.edit', ['tournament' => $tournament]))->name('tournaments.edit');
    });

    // Transmisje i galerie zdjęć meczów ligowych i turniejowych
    Route::view('/media', 'admin.media.index')->middleware('can:media.manage')->name('media.index');

    Route::middleware('can:competitions.manage')->group(function () {
        Route::view('/competitions', 'admin.competitions.index')->name('competitions.index');
        Route::view('/competition-levels', 'admin.competition-levels.index')->name('competition-levels.index');
        Route::view('/seasons', 'admin.seasons.index')->name('seasons.index');
        Route::view('/rival-teams', 'admin.rival-teams.index')->name('rival-teams.index');
    });

    Route::view('/articles', 'admin.articles.index')->middleware('can:articles.manage')->name('articles.index');
    Route::view('/komentarze', 'admin.comments.index')->middleware('can:comments.moderate')->name('comments.index');
    Route::view('/propozycje', 'admin.content-proposals.index')->middleware('can:proposals.content')->name('content-proposals.index');
    Route::view('/hero-slides', 'admin.hero-slides.index')->middleware('can:homepage.manage')->name('hero-slides.index');
    Route::view('/documents', 'admin.documents.index')->middleware('can:documents.manage')->name('documents.index');
    Route::view('/sponsors', 'admin.sponsors.index')->middleware('can:sponsors.manage')->name('sponsors.index');
    Route::view('/users', 'admin.users.index')->middleware('can:users.manage')->name('users.index');
    Route::view('/roles', 'admin.roles.index')->middleware('can:roles.manage')->name('roles.index');
    Route::view('/konta-do-aktywacji', 'admin.accounts.index')->middleware('can:users.activate')->name('accounts.index');

    Route::middleware('can:site.manage')->group(function () {
        Route::view('/settings', 'admin.settings.index')->name('settings.index');
        Route::view('/pages', 'admin.pages.index')->name('pages.index');
    });

    Route::view('/statistics', 'admin.statistics.index')->middleware('can:statistics.view')->name('statistics.index');

    Route::middleware('can:import.run')->group(function () {
        Route::view('/import', 'admin.import.index')->name('import.index');
        Route::get('/import/wzor/{type}', fn (string $type) => response(DataImport::template($type), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="wzor-'.$type.'.csv"',
        ]))->whereIn('type', array_keys(DataImport::TYPES))->name('import.template');
    });

    // Zdjęcia i dane proponowane przez zawodników i rodziców, prośby o zmianę profilu
    Route::middleware('can:proposals.review')->group(function () {
        Route::view('/player-change-requests', 'admin.player-change-requests.index')->name('player-change-requests.index');
        Route::view('/profile-change-requests', 'admin.profile-change-requests.index')->name('profile-change-requests.index');
    });
});

// Stary adres panelu. Na XAMPP /admin bywa zajęty przez narzędzie bazy danych, dlatego panel jest pod /panel.
Route::redirect('/admin', '/panel', 301);
Route::get('/admin/{path}', fn (string $path) => redirect('/panel/'.$path, 301))->where('path', '.*');
