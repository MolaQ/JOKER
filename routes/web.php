<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ClubInfoController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GameCalendarController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageViewController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentMatchController;
use App\Http\Controllers\TrainerController;
use App\Support\LegacyUrls;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Dla wyszukiwarek: mapa strony i zasady indeksowania (adres mapy zależy od domeny).
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Język strony (flagi w nagłówku)
Route::get('/language/{locale}', LocaleController::class)->whereIn('locale', ['pl', 'en'])->name('locale.switch');

// Teams
Route::get('/alumni', [ClubInfoController::class, 'alumni'])->name('alumni.index');
Route::get('/training-schedule', [ClubInfoController::class, 'trainings'])->name('trainings.index');
Route::get('/support-us', [ClubInfoController::class, 'support'])->name('support.index');
Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
Route::get('/galleries/{gallery}', [GalleryController::class, 'show'])->where('gallery', Slug::ROUTE_PATTERN)->name('galleries.show');
Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');

// Articles
Route::get('/news', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/news/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::post('/news/{article}/comments', [ArticleController::class, 'storeComment'])->name('articles.comments.store');

// Players
Route::get('/memorial', [TournamentController::class, 'memorial'])->name('memorial');
Route::get('/tournaments/{tournament}/matches/{match}', [TournamentMatchController::class, 'show'])->where('tournament', Slug::ROUTE_PATTERN)->whereNumber('match')->name('tournaments.matches.show');
Route::get('/tournaments/{tournament}/{category?}', [TournamentController::class, 'show'])->where('tournament', Slug::ROUTE_PATTERN)->where('category', '[a-z0-9-]+')->name('tournaments.show');
Route::get('/players/{player}', [PlayerController::class, 'show'])->where('player', Slug::ROUTE_PATTERN)->name('players.show');

// Trainers
Route::get('/staff/{trainer}', [TrainerController::class, 'show'])->where('trainer', Slug::ROUTE_PATTERN)->name('trainers.show');
Route::get('/family/{user}', [PersonController::class, 'show'])->where('user', Slug::ROUTE_PATTERN)->name('people.show');

// Schedule & Standings
Route::get('/matches', [ScheduleController::class, 'index'])->name('schedule.index');
Route::get('/standings', [ScheduleController::class, 'standings'])->name('standings');
Route::get('/matches/{game}/calendar.ics', [GameCalendarController::class, 'show'])->name('games.calendar');
// Strona meczu: transmisje, galerie, artykuły, polubienia i komentarze (dla zalogowanych).
Route::get('/matches/{game}', [GameController::class, 'show'])->whereNumber('game')->name('games.show');
Route::post('/matches/{game}/comments', [GameController::class, 'storeComment'])->whereNumber('game')->middleware(['auth', 'throttle:20,1'])->name('games.comments.store');

// Wyszukiwarka
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/index.json', [SearchController::class, 'data'])->name('search.data');

// Dokumenty do pobrania
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->whereNumber('document')->name('documents.download');

// Zdjęcia (autorzy zdjęć tematycznych)
Route::view('/photo-credits', 'credits')->name('credits');

// Podstrony edytowane w panelu
Route::get('/page/{page}', [PageController::class, 'show'])->name('pages.show');

// Contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
// Statystyki odwiedzin: czas na stronie, przewinięcie i otwierane zakładki (sendBeacon).
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/stats/view', [PageViewController::class, 'update'])->name('page-views.update');
    Route::post('/stats/tab', [PageViewController::class, 'tab'])->name('page-views.tab');
});

Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

// Powiadomienia push na telefon i komputer
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/notifications/key', [PushSubscriptionController::class, 'key'])->name('push.key');
    Route::post('/notifications/subscription', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/notifications/subscription', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/password/forgot', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/password/forgot', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/resend', [EmailVerificationController::class, 'send'])->middleware('throttle:3,1')->name('verification.send');
    // Laravel kieruje tu osoby bez potwierdzonego adresu; status konta pokazuje "Mój profil".
    Route::redirect('/email/verify', '/my-profile')->name('verification.notice');
});

Route::middleware('auth')->group(function () {
    Route::get('/my-profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::view('/messages', 'messages.index')->name('messages.index');
    Route::post('/my-profile/change-request', [ProfileController::class, 'requestChange'])->name('profile.request-change');
    Route::put('/my-profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/my-profile/image-consent/{player}', [ProfileController::class, 'updateConsent'])->where('player', Slug::ROUTE_PATTERN)->name('profile.consent');

    // Zgoda RODO i stan konta: ponowna prośba po odrzuceniu, wyłączenie, usunięcie, pobranie danych.
    Route::get('/consents', [AccountController::class, 'consent'])->name('account.consent');
    Route::post('/consents', [AccountController::class, 'storeConsent'])->name('account.consent.store');
    Route::post('/my-profile/resubmit', [AccountController::class, 'resubmit'])->name('account.resubmit');
    Route::post('/my-profile/disable', [AccountController::class, 'disable'])->name('account.disable');
    Route::post('/my-profile/enable', [AccountController::class, 'enable'])->name('account.enable');
    Route::get('/my-profile/my-data', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/my-profile', [AccountController::class, 'destroy'])->name('account.destroy');
});

/*
 * Stare adresy (polskie i jeszcze starsze) - stałe przekierowania na angielskie, by nie zgubić linków i pozycji w Google.
 * Tłumaczymy każdy człon adresu, np. /turnieje/3-memorial/mecze/5 → /tournaments/3-memorial/matches/5.
 */
foreach (array_keys(LegacyUrls::PREFIXES) as $oldPath) {
    Route::get($oldPath.'/{rest?}', fn (Request $request) => redirect(LegacyUrls::translate($request->getRequestUri()), 301))
        ->where('rest', '.*');
}
