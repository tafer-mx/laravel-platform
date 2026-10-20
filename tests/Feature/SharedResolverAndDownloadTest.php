<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
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

it('downloads a PDF from allowed Storyblok host with clean filename', function () {
    Route::get('/download-test', DownloadController::class);

    Http::fake([
        'https://a.storyblok.com/*' => Http::response('PDF binary stream content', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => '25',
        ]),
    ]);

    $response = $this->get('/download-test?'.http_build_query([
        'url' => 'https://a.storyblok.com/f/12345/menu-summer.pdf',
        'filename' => 'menu summer',
    ]));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename=menu_summer.pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->streamedContent())->toBe('PDF binary stream content');
});

it('rejects downloader requests without a URL', function () {
    Route::get('/download-test', DownloadController::class);

    $this->get('/download-test')->assertStatus(400);
});

it('rejects non-HTTPS schemes', function () {
    Route::get('/download-test', DownloadController::class);

    $this->get('/download-test?url='.urlencode('http://a.storyblok.com/test.pdf'))->assertStatus(404);
    $this->get('/download-test?url='.urlencode('data://text/plain,hello'))->assertStatus(404);
    $this->get('/download-test?url='.urlencode('file:///etc/passwd'))->assertStatus(404);
});

it('rejects unapproved external hosts', function () {
    Route::get('/download-test', DownloadController::class);

    $this->get('/download-test?url='.urlencode('https://malicious-site.com/evil.pdf'))->assertStatus(404);
    $this->get('/download-test?url='.urlencode('https://a.storyblok.com.attacker.com/evil.pdf'))->assertStatus(404);
});

it('returns 404 cleanly when upstream resource is missing without exposing errors', function () {
    Route::get('/download-test', DownloadController::class);

    Http::fake([
        'https://a.storyblok.com/*' => Http::response('Not Found', 404),
    ]);

    $this->get('/download-test?url='.urlencode('https://a.storyblok.com/f/missing.pdf'))
        ->assertStatus(404);
});
