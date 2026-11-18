<?php

namespace App\Providers;

use App\Models\User;
use App\Support\ArticleCover;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ArticleCover::class);
    }

    public function boot(): void
    {
        Gate::define('manage-content', fn (User $user) => $user->is_admin);
    }
}
