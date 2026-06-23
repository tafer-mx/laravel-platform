<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Storyblok\Api\Domain\Value\Dto\Version;
use TAFER\Core\Contracts\StoryblokCache;
use TAFER\Core\Contracts\StoryblokCacheInvalidator;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Http\Controllers\StoryblokWebhookController;
use TAFER\Core\Storyblok\CachedStory;
use TAFER\Core\Storyblok\LaravelStoryblokCache;
use TAFER\Core\Storyblok\StoryblokCacheContext;
use TAFER\Core\Storyblok\StoryblokCacheKey;
use TAFER\Core\Storyblok\StoryblokIdentity;
use TAFER\Core\Storyblok\StoryblokSlugNormalizer;
use TAFER\Core\Storyblok\StoryblokWebhookInvalidator;

uses(TestCase::class);

it('invalidates both locales from a Storyblok webhook payload', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    app()->singleton(StoryblokCache::class, fn () => new LaravelStoryblokCache(
        new Repository(new ArrayStore),
        new StoryblokCacheKey('test:storyblok'),
    ));
    app()->singleton(
        StoryblokCacheInvalidator::class,
        fn () => app(StoryblokCache::class),
    );
    app()->singleton(
        StoryblokWebhookInvalidator::class,
        fn () => new StoryblokWebhookInvalidator(
            app(StoryblokCacheInvalidator::class),
            new StoryblokSlugNormalizer,
        ),
    );

    Route::post('/storyblok/webhook-test', StoryblokWebhookController::class);

    $cache = app(StoryblokCache::class);
    $uuid = '550e8400-e29b-41d4-a716-446655440001';
    $slug = 'brands/garza-blanca/puerto-vallarta/config_brand_puerto-vallarta';

    foreach ([Locale::English, Locale::Spanish] as $locale) {
        $cache->put(
            new StoryblokIdentity($slug, $locale, $uuid),
            CachedStory::fromRelation([
                'uuid' => $uuid,
                'full_slug' => $locale === Locale::Spanish ? 'es/'.$slug : $slug,
            ], 1),
            new StoryblokCacheContext($locale, Version::Published),
        );
    }

    $response = $this->postJson('/storyblok/webhook-test', [
        'text' => "The user hector.lozano@tafersales.com published the Story config_brand_puerto-vallarta ({$slug})\nhttps://app.storyblok.com/#/me/spaces/285826016720786/stories/0/0/87702217043285",
        'action' => 'published',
        'space_id' => 285826016720786,
        'story_id' => 87702217043285,
        'full_slug' => $slug,
        'full_slug__i18n__es' => 'es/'.$slug,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Cache invalidated')
        ->assertJsonPath('story_id', 87702217043285)
        ->assertJsonPath('space_id', 285826016720786)
        ->assertJsonPath('slug', $slug)
        ->assertJsonPath('action', 'published')
        ->assertJsonPath('both_languages', true)
        ->assertJsonPath('languages', ['en', 'es'])
        ->assertJsonPath('delete_results.en.successful', true)
        ->assertJsonPath('delete_results.es.successful', true)
        ->assertJsonPath('delete_results.en.payload_existed', true)
        ->assertJsonPath('delete_results.es.payload_existed', true);

    expect($cache->get(
        new StoryblokIdentity($slug, Locale::English),
        new StoryblokCacheContext(Locale::English, Version::Published),
    ))->toBeNull()
        ->and($cache->get(
            new StoryblokIdentity($slug, Locale::Spanish),
            new StoryblokCacheContext(Locale::Spanish, Version::Published),
        ))->toBeNull();
});
