<?php

namespace App\Providers;

use App\Models\Interaction;
use App\Models\Post;
use App\Observers\InteractionObserver;
use App\Observers\PostObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Post::observe(PostObserver::class);
        Interaction::observe(InteractionObserver::class);
    }
}
