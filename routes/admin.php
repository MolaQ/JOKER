<?php

use App\Http\Controllers\Admin\ApprovalsController;
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

    Route::view('/plan-treningow', 'admin.trainings.index')->middleware('can:trainings.manage')->name('trainings.index');
    Route::view('/wychowankowie', 'admin.alumni.index')->middleware('can:articles.manage')->name('alumni.index');
    Route::view('/wsparcie', 'admin.support.index')->middleware('can:sponsors.manage')->name('support.index');

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
    Route::view('/galerie', 'admin.galleries.index')->middleware('can:media.manage')->name('galleries.index');

    Route::middleware('can:competitions.manage')->group(function () {
        Route::view('/competitions', 'admin.competitions.index')->name('competitions.index');
        Route::view('/seasons', 'admin.seasons.index')->name('seasons.index');
        Route::view('/rival-teams', 'admin.rival-teams.index')->name('rival-teams.index');
    });

    Route::view('/articles', 'admin.articles.index')->middleware('can:articles.manage')->name('articles.index');
    Route::view('/hero-slides', 'admin.hero-slides.index')->middleware('can:homepage.manage')->name('hero-slides.index');
    Route::view('/documents', 'admin.documents.index')->middleware('can:documents.manage')->name('documents.index');
    Route::view('/sponsors', 'admin.sponsors.index')->middleware('can:sponsors.manage')->name('sponsors.index');
    Route::view('/users', 'admin.users.index')->middleware('can:users.manage')->name('users.index');
    Route::view('/roles', 'admin.roles.index')->middleware('can:roles.manage')->name('roles.index');

    Route::middleware('can:site.manage')->group(function () {
        Route::view('/settings', 'admin.settings.index')->name('settings.index');
        Route::view('/pages', 'admin.pages.index')->name('pages.index');
        Route::view('/seo', 'admin.seo.index')->name('seo.index');
    });

    Route::view('/statistics', 'admin.statistics.index')->middleware('can:statistics.view')->name('statistics.index');

    Route::middleware('can:import.run')->group(function () {
        Route::view('/import', 'admin.import.index')->name('import.index');
        Route::get('/import/wzor/{type}', fn (string $type) => response(DataImport::template($type), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="wzor-'.$type.'.csv"',
        ]))->whereIn('type', array_keys(DataImport::TYPES))->name('import.template');
    });

    // Do zatwierdzenia: jedna strona z zakładkami (App\Support\Approvals), każda zakładka pod własnym adresem
    Route::get('/do-zatwierdzenia', ApprovalsController::class)->name('approvals.index');
    Route::get('/konta-do-aktywacji', ApprovalsController::class)->defaults('section', 'konta')->name('accounts.index');
    Route::get('/propozycje', ApprovalsController::class)->defaults('section', 'propozycje')->name('content-proposals.index');
    Route::get('/komentarze', ApprovalsController::class)->defaults('section', 'komentarze')->name('comments.index');
    Route::get('/oznaczenia-zdjec', ApprovalsController::class)->defaults('section', 'oznaczenia')->name('photo-tags.index');
    Route::get('/player-change-requests', ApprovalsController::class)->defaults('section', 'zawodnicy')->name('player-change-requests.index');
    Route::get('/profile-change-requests', ApprovalsController::class)->defaults('section', 'profile')->name('profile-change-requests.index');
});

// Stary adres panelu. Na XAMPP /admin bywa zajęty przez narzędzie bazy danych, dlatego panel jest pod /panel.
Route::redirect('/admin', '/panel', 301);
Route::get('/admin/{path}', fn (string $path) => redirect('/panel/'.$path, 301))->where('path', '.*');
