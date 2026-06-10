<?php

namespace TAFER\Core;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Storyblok\Api\StoriesApi;
use Storyblok\Api\StoriesApiInterface;
use Storyblok\Api\StoryblokClient;
use Storyblok\Api\StoryblokClientInterface;
use TAFER\Core\Contracts\ReviewClient;
use TAFER\Core\Services\ReviewsService;
use TAFER\Core\Services\StoryblokService;
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

        $this->app->singleton(StoryblokClientInterface::class, fn () => new StoryblokClient(
            config('tafer.storyblok.base_uri', 'https://api.storyblok.com'),
            config('tafer.storyblok.token', ''),
            config('tafer.storyblok.timeout', 4),
        ));

        $this->app->singleton(StoriesApiInterface::class, fn () => new StoriesApi(
            $this->app->make(StoryblokClientInterface::class),
            config('tafer.storyblok.version', 'published'),
        ));

        $this->app->singleton(StoryblokService::class, fn () => new StoryblokService(
            $this->app->make(StoriesApiInterface::class),
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
