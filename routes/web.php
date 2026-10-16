<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GameCalendarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TrainerController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Teams
Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');

// Articles
Route::get('/news', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/news/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::post('/news/{article}/comments', [ArticleController::class, 'storeComment'])->name('articles.comments.store');

// Players
Route::get('/players/{player}', [PlayerController::class, 'show'])->name('players.show');

// Trainers
Route::get('/trenerzy/{trainer}', [TrainerController::class, 'show'])->name('trainers.show');

// Schedule & Standings
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
Route::get('/standings', [ScheduleController::class, 'standings'])->name('standings');
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
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send');

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/request-change', [ProfileController::class, 'requestChange'])->name('profile.request-change');
});
