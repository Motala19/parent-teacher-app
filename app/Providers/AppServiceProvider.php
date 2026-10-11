<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;

class AppServiceProvider extends ServiceProvider
{
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
        // Redirect user to the correct dashboard after login based on role
        Event::listen(Login::class, function ($event) {
            $user = $event->user;
            session(['url.intended' => route($user->getDashboardRoute())]);
        });
    }
}