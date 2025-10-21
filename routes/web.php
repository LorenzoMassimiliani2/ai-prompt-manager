<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\CommentController; 
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TagController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]); 
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('prompts', PromptController::class);
    Route::post('prompts/{prompt}/favorite', [PromptController::class, 'toggleFavorite'])
        ->name('prompts.favorite');

    Route::resource('tags', TagController::class)->only(['store','update','destroy']);

    Route::post('prompts/{prompt}/comments', [CommentController::class, 'store'])->name('prompts.comments.store');
    Route::delete('prompts/{prompt}/comments/{comment}', [CommentController::class, 'destroy'])->name('prompts.comments.destroy');

     Route::resource('services', ServiceController::class)
         ->except(['show']) // se non ti serve la show
         ->middleware('can:viewAny,'.\App\Models\Service::class);

});

Route::get('/', fn() => redirect()->route('prompts.index'));

Route::middleware(['auth', 'verified'])->group(function () {
});
require __DIR__.'/auth.php';
