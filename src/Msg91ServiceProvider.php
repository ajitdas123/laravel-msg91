<?php

declare(strict_types=1);

namespace Madgeek\Msg91;

use Illuminate\Support\ServiceProvider;

class Msg91ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Msg91::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/laravel-msg91.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-msg91');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laravel-msg91');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-msg91'),
        ], ['laravel-msg91', 'laravel-msg91-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/laravel-msg91'),
        ], ['laravel-msg91', 'laravel-msg91-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/laravel-msg91'),
        ], ['laravel-msg91', 'laravel-msg91-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['laravel-msg91', 'laravel-msg91-migrations']);
    }
}
