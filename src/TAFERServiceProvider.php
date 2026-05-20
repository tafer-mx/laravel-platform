<?php

namespace TAFER\Core;

use Illuminate\Support\ServiceProvider;

class TAFERServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'tafer');
    }
}