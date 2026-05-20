<?php

namespace TAFER\Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use TAFER\Core\View\Components\PhoneDirectory;
class TAFERServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'tafer');

        // Class Components
        // https://laravel.com/docs/13.x/packages#view-components
        Blade::component('tafer-phone-directory', PhoneDirectory::class);

    }
}