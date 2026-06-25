<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        config([
            'tafer.brand.slug' => env('TAFER_BRAND_SLUG', 'villa-palmar-cancun'),
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $path = function_exists('workbench_path')
        ? \workbench_path('resources/views')
        : base_path('workbench/resources/views');

        $this->loadViewsFrom($path, 'workbench');
    }
}
