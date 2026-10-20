<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GameCalendarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageViewController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TrainerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Teams
Route::get('/druzyny', [TeamController::class, 'index'])->name('teams.index');
Route::get('/druzyny/{team}', [TeamController::class, 'show'])->name('teams.show');

// Articles
Route::get('/aktualnosci', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/aktualnosci/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::post('/aktualnosci/{article}/komentarze', [ArticleController::class, 'storeComment'])->name('articles.comments.store');

// Players
Route::get('/zawodnicy/{player}', [PlayerController::class, 'show'])->name('players.show');

// Trainers
Route::get('/sztab/{trainer}', [TrainerController::class, 'show'])->name('trainers.show');

// Schedule & Standings
Route::get('/mecze', [ScheduleController::class, 'index'])->name('schedule.index');
Route::get('/tabele', [ScheduleController::class, 'standings'])->name('standings');
Route::get('/mecze/{game}/kalendarz.ics', [GameCalendarController::class, 'show'])->name('games.calendar');

// Wyszukiwarka
Route::get('/szukaj', [SearchController::class, 'index'])->name('search');
Route::get('/szukaj/indeks.json', [SearchController::class, 'data'])->name('search.data');

// Dokumenty do pobrania
Route::get('/dokumenty', [DocumentController::class, 'index'])->name('documents.index');

// Zdjęcia (autorzy zdjęć tematycznych)
Route::view('/zdjecia', 'credits')->name('credits');

// Podstrony edytowane w panelu
Route::get('/strona/{page}', [PageController::class, 'show'])->name('pages.show');

// Contact
Route::get('/kontakt', [ContactController::class, 'index'])->name('contact.index');
// Statystyki odwiedzin: czas na stronie, przewinięcie i otwierane zakładki (sendBeacon).
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/statystyki/wyswietlenie', [PageViewController::class, 'update'])->name('page-views.update');
    Route::post('/statystyki/zakladka', [PageViewController::class, 'tab'])->name('page-views.tab');
});

Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/moj-profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/moj-profil/prosba-o-zmiane', [ProfileController::class, 'requestChange'])->name('profile.request-change');
});

/*
 * Stare adresy (sprzed polskich, przyjaznych SEO) - stałe przekierowania, by nie zgubić linków i pozycji w Google.
 */
foreach ([
    'teams' => 'druzyny',
    'news' => 'aktualnosci',
    'players' => 'zawodnicy',
    'trenerzy' => 'sztab',
    'schedule' => 'mecze',
    'standings' => 'tabele',
    'contact' => 'kontakt',
    'profile' => 'moj-profil',
] as $oldPath => $newPath) {
    Route::get($oldPath.'/{rest?}', fn (Request $request, ?string $rest = null) => redirect(
        '/'.$newPath.($rest ? '/'.$rest : '').($request->getQueryString() ? '?'.$request->getQueryString() : ''),
        301
    ))->where('rest', '.*');
}
