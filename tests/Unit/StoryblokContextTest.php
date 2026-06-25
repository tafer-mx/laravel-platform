<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Storyblok\Api\Domain\Value\Dto\Version;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Storyblok\CachedStory;
use TAFER\Core\Storyblok\LaravelStoryblokCache;
use TAFER\Core\Storyblok\StoryblokCacheContext;
use TAFER\Core\Storyblok\StoryblokCacheKey;
use TAFER\Core\Storyblok\StoryblokIdentity;
use TAFER\Core\Storyblok\StoryblokWebhookInvalidator;

it('invalidates both locales when a webhook reports both translations', function () {
    $repository = new Repository(new ArrayStore);
    $cache = new LaravelStoryblokCache(
        $repository,
        new StoryblokCacheKey('test:storyblok'),
    );
    $webhook = new StoryblokWebhookInvalidator($cache);
    $uuid = '550e8400-e29b-41d4-a716-446655440001';

    foreach (Locale::cases() as $locale) {
        $context = new StoryblokCacheContext($locale, Version::Published);
        $identity = new StoryblokIdentity('relations/suite', $locale, $uuid);
        $cache->put(
            $identity,
            CachedStory::fromRelation([
                'uuid' => $uuid,
                'full_slug' => $locale === Locale::Spanish
                    ? 'es/relations/suite'
                    : 'relations/suite',
            ], 1),
            $context,
        );
    }

    $results = $webhook->invalidateLocales(
        'es/relations/suite',
        [Locale::English, Locale::Spanish],
    );

    expect($results['en']->successful())->toBeTrue()
        ->and($results['es']->successful())->toBeTrue()
        ->and($cache->getByUuid(
            $uuid,
            new StoryblokCacheContext(Locale::English, Version::Published),
        ))->toBeNull()
        ->and($cache->getByUuid(
            $uuid,
            new StoryblokCacheContext(Locale::Spanish, Version::Published),
        ))->toBeNull();
});
