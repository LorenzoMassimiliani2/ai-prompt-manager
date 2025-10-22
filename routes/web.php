<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\CommentController; 
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PromptFolderController; 
use App\Http\Controllers\FolderController;
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


Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('prompts', PromptController::class);
    Route::post('prompts/{prompt}/favorite', [PromptController::class, 'toggleFavorite'])
        ->name('prompts.favorite');
    
        Route::post('/prompts/{prompt}/folders', [PromptFolderController::class, 'attach'])
    ->name('prompts.folders.attach');
    
    Route::delete('/prompts/{prompt}/folders/{folder}', [PromptFolderController::class, 'detach'])
    ->name('prompts.folders.detach');    

    Route::resource('tags', TagController::class)->only(['store','update','destroy']);

    Route::get('prompts/create', [PromptController::class,'create'])->name('prompts.create'); // già esistente
    Route::post('prompts/{prompt}/comments', [CommentController::class, 'store'])->name('prompts.comments.store');
    Route::delete('prompts/{prompt}/comments/{comment}', [CommentController::class, 'destroy'])->name('prompts.comments.destroy');

     Route::resource('services', ServiceController::class)
         ->except(['show']) // se non ti serve la show
         ->middleware('can:viewAny,'.\App\Models\Service::class);

    Route::post('/folders',               [FolderController::class, 'store'])->name('folders.store');
    Route::put('/folders/{folder}',       [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}',    [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('/folders/{folder}/move', [FolderController::class, 'move'])->name('folders.move');

    Route::post('/folders/{folder}/attach-prompt/{prompt}', [FolderController::class, 'attachPrompt'])->name('folders.attach');
    Route::delete('/folders/{folder}/detach-prompt/{prompt}',[FolderController::class, 'detachPrompt'])->name('folders.detach');     

});

Route::get('/', fn() => redirect()->route('prompts.index'));

Route::middleware(['auth', 'verified'])->group(function () {
});
require __DIR__.'/auth.php';
