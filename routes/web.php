<?php

use App\Http\Controllers\AdminArticleController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\EditorialSessionController;
use App\Http\Controllers\PublicationController;
use App\Http\Middleware\EditorialInertia;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

$editorialOnly = [
    EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class,
    ShareErrorsFromSession::class, PreventRequestForgery::class, EditorialInertia::class,
];

Route::withoutMiddleware($editorialOnly)->group(function () {
    Route::get('/', [PublicationController::class, 'home'])->name('home');
    Route::get('/index.php', fn () => redirect()->route('home', status: 301));
    Route::get('/articole.php', [PublicationController::class, 'index'])->name('publication.index');
    Route::get('/articol.php', [PublicationController::class, 'legacy']);
    Route::get('/cauta', [PublicationController::class, 'search'])->name('publication.search');
    Route::get('/sitemap.xml', [PublicationController::class, 'sitemap']);
    Route::get('/robots.txt', [PublicationController::class, 'robots']);
    Route::get('/ads.txt', [PublicationController::class, 'ads']);
    Route::get('/coperti/{file}', [PublicationController::class, 'image'])
        ->where('file', '[a-zA-Z0-9-]+\.(?:jpe?g|png|webp)')->name('publication.image');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [EditorialSessionController::class, 'create'])->name('login');
    Route::post('/login', [EditorialSessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::middleware(['auth', 'can:manage-content'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.articles.index'));
    Route::post('/logout', [EditorialSessionController::class, 'destroy'])->name('logout');
    Route::resource('articole', AdminArticleController::class)->except(['show', 'destroy'])
        ->parameters(['articole' => 'article'])->names('articles');
    Route::get('/categorii', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categorii', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categorii/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
});

Route::get('/{slug}', [PublicationController::class, 'show'])
    ->withoutMiddleware($editorialOnly)->name('publication.show');
