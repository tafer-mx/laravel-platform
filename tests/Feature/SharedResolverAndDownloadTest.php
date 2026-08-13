<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;
use TAFER\Core\Http\Controllers\DownloadController;
use TAFER\Core\TAFERServiceProvider;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(TAFERServiceProvider::class);
    View::addLocation(__DIR__.'/../fixtures/views');
});

it('renders legacy and kebab-case components through the shared resolver alias', function () {
    $html = Blade::render(
        '<x-storyblok._resolver :bloks="$bloks" :global-config="$globalConfig" />',
        [
            'bloks' => [
                ['component' => 'Legacy_Component', 'title' => 'Legacy'],
                ['component' => 'kebab-component', 'title' => 'Modern'],
            ],
            'globalConfig' => ['brand' => 'TAFER'],
        ],
    );

    expect($html)
        ->toContain('data-component="legacy"')
        ->toContain('Legacy')
        ->toContain('data-component="kebab"')
        ->toContain('Modern:TAFER');
});

it('renders the consumer unknown component when there are no valid bloks', function () {
    $html = Blade::render('<x-storyblok._resolver :bloks="[]" />');

    expect($html)->toContain('data-component="unknown"');
});

it('keeps the legacy downloader response and filename behavior', function () {
    Route::get('/download-test', DownloadController::class);

    // This non-HTTP stream intentionally proves compatibility with the legacy
    // implementation. The controller documents that arbitrary schemes are a
    // known security flaw to remove in a dedicated follow-up.
    $response = $this->get('/download-test?'.http_build_query([
        'url' => 'data://text/plain,PDF content',
        'filename' => 'menu summer',
    ]));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/octet-stream')
        ->assertHeader('Content-Disposition', 'attachment; filename="menu_summer.pdf"')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertSeeText('PDF content');
});

it('rejects downloader requests without a URL', function () {
    Route::get('/download-test', DownloadController::class);

    $this->get('/download-test')->assertStatus(400);
});
