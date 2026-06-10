<?php

namespace TAFER\Core;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use TAFER\Core\Contracts\ReviewClient;
use TAFER\Core\Services\ReviewsService;
use TAFER\Core\View\Components\PhoneDirectory;

class TAFERServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReviewClient::class, fn () => new ReviewsService(
            new Client([
                'base_uri' => config('tafer.middleware.base_url'),
                'timeout' => config('tafer.middleware.reviews.timeout'),
            ])
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tafer');

        // Class Components
        // https://laravel.com/docs/13.x/packages#view-components
        Blade::component('tafer-phone-directory', PhoneDirectory::class);

    }
}
