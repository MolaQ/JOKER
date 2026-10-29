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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Dla wyszukiwarek: mapa strony i zasady indeksowania (adres mapy zależy od domeny).
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Język strony (flagi w nagłówku)
Route::get('/jezyk/{locale}', LocaleController::class)->whereIn('locale', ['pl', 'en'])->name('locale.switch');

// Teams
Route::get('/wychowankowie', [ClubInfoController::class, 'alumni'])->name('alumni.index');
Route::get('/plan-treningow', [ClubInfoController::class, 'trainings'])->name('trainings.index');
Route::get('/wspieraj-klub', [ClubInfoController::class, 'support'])->name('support.index');
Route::get('/galerie', [GalleryController::class, 'index'])->name('galleries.index');
Route::get('/galerie/{gallery}', [GalleryController::class, 'show'])->name('galleries.show');
Route::get('/druzyny', [TeamController::class, 'index'])->name('teams.index');
Route::get('/druzyny/{team}', [TeamController::class, 'show'])->name('teams.show');

// Articles
Route::get('/aktualnosci', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/aktualnosci/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::post('/aktualnosci/{article}/komentarze', [ArticleController::class, 'storeComment'])->name('articles.comments.store');

// Players
Route::get('/memorial', [TournamentController::class, 'memorial'])->name('memorial');
Route::get('/turnieje/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
Route::get('/turnieje/{tournament}/mecze/{match}', [TournamentMatchController::class, 'show'])->whereNumber('match')->name('tournaments.matches.show');
Route::get('/zawodnicy/{player}', [PlayerController::class, 'show'])->name('players.show');

// Trainers
Route::get('/sztab/{trainer}', [TrainerController::class, 'show'])->name('trainers.show');
Route::get('/osoby/{user}', [PersonController::class, 'show'])->name('people.show');

// Schedule & Standings
Route::get('/mecze', [ScheduleController::class, 'index'])->name('schedule.index');
Route::get('/tabele', [ScheduleController::class, 'standings'])->name('standings');
Route::get('/mecze/{game}/kalendarz.ics', [GameCalendarController::class, 'show'])->name('games.calendar');
// Strona meczu: transmisje, galerie, artykuły, polubienia i komentarze (dla zalogowanych).
Route::get('/mecze/{game}', [GameController::class, 'show'])->whereNumber('game')->name('games.show');
Route::post('/mecze/{game}/komentarze', [GameController::class, 'storeComment'])->whereNumber('game')->middleware(['auth', 'throttle:20,1'])->name('games.comments.store');

// Wyszukiwarka
Route::get('/szukaj', [SearchController::class, 'index'])->name('search');
Route::get('/szukaj/indeks.json', [SearchController::class, 'data'])->name('search.data');

// Dokumenty do pobrania
Route::get('/dokumenty', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/dokumenty/{document}/pobierz', [DocumentController::class, 'download'])->whereNumber('document')->name('documents.download');

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

// Powiadomienia push na telefon i komputer
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/powiadomienia/klucz', [PushSubscriptionController::class, 'key'])->name('push.key');
    Route::post('/powiadomienia/subskrypcja', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/powiadomienia/subskrypcja', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('/rejestracja', [RegisterController::class, 'create'])->name('register');
    Route::post('/rejestracja', [RegisterController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/haslo/przypomnij', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/haslo/przypomnij', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/haslo/nowe/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/haslo/nowe', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/potwierdz/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/wyslij-ponownie', [EmailVerificationController::class, 'send'])->middleware('throttle:3,1')->name('verification.send');
    // Laravel kieruje tu osoby bez potwierdzonego adresu; status konta pokazuje "Mój profil".
    Route::redirect('/email/potwierdz', '/moj-profil')->name('verification.notice');
});

Route::middleware('auth')->group(function () {
    Route::get('/moj-profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::view('/wiadomosci', 'messages.index')->name('messages.index');
    Route::post('/moj-profil/prosba-o-zmiane', [ProfileController::class, 'requestChange'])->name('profile.request-change');
    Route::put('/moj-profil/haslo', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/moj-profil/zgoda-na-wizerunek/{player}', [ProfileController::class, 'updateConsent'])->name('profile.consent');

    // Zgoda RODO i stan konta: ponowna prośba po odrzuceniu, wyłączenie, usunięcie, pobranie danych.
    Route::get('/zgody', [AccountController::class, 'consent'])->name('account.consent');
    Route::post('/zgody', [AccountController::class, 'storeConsent'])->name('account.consent.store');
    Route::post('/moj-profil/ponowna-prosba', [AccountController::class, 'resubmit'])->name('account.resubmit');
    Route::post('/moj-profil/wylacz', [AccountController::class, 'disable'])->name('account.disable');
    Route::post('/moj-profil/wlacz', [AccountController::class, 'enable'])->name('account.enable');
    Route::get('/moj-profil/moje-dane', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/moj-profil', [AccountController::class, 'destroy'])->name('account.destroy');
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
