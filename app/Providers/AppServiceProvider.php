<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        \App\Models\Prompt::class => \App\Policies\PromptPolicy::class,
        \App\Models\Tag::class => \App\Policies\TagPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Gate::define('manage-tags', fn(\App\Models\User $user) => (bool)$user->is_superuser);

    }
}
