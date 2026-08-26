<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Orchestra\Testbench\TestCase;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoryResponse;
use Storyblok\Api\Request\StoriesRequest;
use Storyblok\Api\Response\StoriesResponse;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Services\CachedStoryblokService;
use TAFER\Core\Storyblok\CachedStory;
use TAFER\Core\Storyblok\LaravelStoryblokCache;
use TAFER\Core\Storyblok\StoryblokCacheContext;
use TAFER\Core\Storyblok\StoryblokCacheKey;
use TAFER\Core\Storyblok\StoryblokCachePolicy;
use TAFER\Core\Storyblok\StoryblokIdentity;
use TAFER\Core\Storyblok\StoryblokRequestFactory;

uses(TestCase::class);

function cachedStoryblokResponse(array $story = [], array $relations = []): StoryResponse
{
    return new StoryResponse([
        'story' => array_merge([
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'full_slug' => 'home',
            'name' => 'Home',
        ], $story),
        'cv' => 1,
        'rels' => $relations,
        'rel_uuids' => array_values(array_filter(array_column($relations, 'uuid'))),
        'links' => [],
    ]);
}

function countingStoryblokGateway(StoryResponse $response): StoryblokGateway
{
    return new class($response) implements StoryblokGateway
    {
        public int $slugCalls = 0;

        public int $uuidCalls = 0;

        public ?string $lastSlug = null;

        public ?StoryRequest $lastRequest = null;

        public function __construct(
            private StoryResponse $response,
        ) {}

        public function getStory(string $slug, ?StoryRequest $request = null): StoryResponse
        {
            $this->slugCalls++;
            $this->lastSlug = $slug;
            $this->lastRequest = $request;

            return $this->response;
        }

        public function getStoryByUuid(string|Uuid $uuid, ?StoryRequest $request = null): StoryResponse
        {
            $this->uuidCalls++;
            $this->lastRequest = $request;

            return $this->response;
        }

        public function resolveRelation(mixed $relation, bool $draft = false, string $lang = 'en'): ?array
        {
            // Mock implementation for testing
            return null;
        }
        
        public function getStoriesByContentType(string $contentType, ?StoriesRequest $request = null): StoriesResponse
        {
            throw new \BadMethodCallException('getStoriesByContentType was not expected in this test.');
        }
    };
}

/**
 * @return array{0: LaravelStoryblokCache, 1: Repository}
 */
function storyblokCacheFixture(): array
{
    $repository = new Repository(new ArrayStore);

    return [
        new LaravelStoryblokCache(
            $repository,
            new StoryblokCacheKey('test:storyblok'),
        ),
        $repository,
    ];
}

function cachedStoryblokService(
    StoryblokGateway $origin,
    LaravelStoryblokCache $cache,
): CachedStoryblokService {
    return new CachedStoryblokService(
        origin: $origin,
        cache: $cache,
        requests: new StoryblokRequestFactory([
            'Element_references.Content_info_pages',
        ]),
        policy: new StoryblokCachePolicy,
    );
}

function deferredCallbacks(): DeferredCallbackCollection
{
    return app(DeferredCallbackCollection::class);
}

it('defers caching the parent until the deferred callback runs', function () {
    [$cache] = storyblokCacheFixture();
    $origin = countingStoryblokGateway(cachedStoryblokResponse());
    $service = cachedStoryblokService($origin, $cache);

    $service->getStory('home');
    $service->getStory('home');

    expect($origin->slugCalls)->toBe(2)
        ->and(deferredCallbacks())->toHaveCount(1);

    deferredCallbacks()->invoke();
    $service->getStory('home');

    expect($origin->slugCalls)->toBe(2);
});

it('caches every resolved relation as an independent story', function () {
    $relation = [
        'uuid' => '550e8400-e29b-41d4-a716-446655440001',
        'full_slug' => 'es/brands/mousai/puerto-vallarta/suites/north',
        'name' => 'North Suite',
    ];
    [$cache] = storyblokCacheFixture();
    $origin = countingStoryblokGateway(cachedStoryblokResponse(
        ['full_slug' => 'es/brands/mousai/puerto-vallarta/suites'],
        [$relation],
    ));
    $service = cachedStoryblokService($origin, $cache);
    $request = new StoryRequest(language: 'es');

    $service->getStory('es/brands/mousai/puerto-vallarta/suites', $request);

    expect($origin->lastSlug)->toBe('brands/mousai/puerto-vallarta/suites')
        ->and($origin->lastRequest?->withRelations->toString())
        ->toBe('Element_references.Content_info_pages');

    deferredCallbacks()->invoke();
    $cached = $service->getStoryByUuid($relation['uuid'], $request);

    expect($cached->story)->toBe($relation)
        ->and($origin->uuidCalls)->toBe(0);
});

it('does not replace a relation that is already cached', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440001';
    $identity = new StoryblokIdentity('relations/suite', Locale::English, $uuid);
    $context = new StoryblokCacheContext(Locale::English, Version::Published);
    [$cache] = storyblokCacheFixture();
    $cache->put(
        $identity,
        CachedStory::fromRelation([
            'uuid' => $uuid,
            'full_slug' => 'relations/suite',
            'name' => 'Webhook-authoritative version',
        ], 1),
        $context,
        isRelation: true,
    );
    $origin = countingStoryblokGateway(cachedStoryblokResponse(relations: [[
        'uuid' => $uuid,
        'full_slug' => 'relations/suite',
        'name' => 'Parent response version',
    ]]));
    $service = cachedStoryblokService($origin, $cache);

    $service->getStory('home');
    deferredCallbacks()->invoke();

    expect($cache->getByUuid($uuid, $context)?->story['name'])
        ->toBe('Webhook-authoritative version');
});

it('does not cache draft requests', function () {
    [$cache] = storyblokCacheFixture();
    $origin = countingStoryblokGateway(cachedStoryblokResponse());
    $service = cachedStoryblokService($origin, $cache);
    $request = new StoryRequest(version: Version::Draft);

    $service->getStory('home', $request);
    $service->getStory('home', $request);

    expect($origin->slugCalls)->toBe(2)
        ->and(deferredCallbacks())->toHaveCount(0);
});

it('separates cached stories by locale', function () {
    [$cache] = storyblokCacheFixture();
    $origin = countingStoryblokGateway(cachedStoryblokResponse());
    $service = cachedStoryblokService($origin, $cache);

    $service->getStory('home', new StoryRequest(language: 'en'));
    deferredCallbacks()->invoke();
    $service->getStory('home', new StoryRequest(language: 'es'));
    deferredCallbacks()->invoke();
    $service->getStory('home', new StoryRequest(language: 'en'));

    expect($origin->slugCalls)->toBe(2);
});

it('removes dangling uuid indexes before returning a cache miss', function () {
    [$cache, $repository] = storyblokCacheFixture();
    $keys = new StoryblokCacheKey('test:storyblok');
    $context = new StoryblokCacheContext(Locale::English, Version::Published);
    $identity = new StoryblokIdentity('relations/missing', Locale::English);
    $uuid = '550e8400-e29b-41d4-a716-446655440001';

    $repository->forever($keys->uuid($uuid, $context), $identity->canonicalSlug);
    $repository->forever($keys->slugUuid($identity, $context), $uuid);

    expect($cache->getByUuid($uuid, $context))->toBeNull()
        ->and($repository->has($keys->uuid($uuid, $context)))->toBeFalse()
        ->and($repository->has($keys->slugUuid($identity, $context)))->toBeFalse();
});

it('returns detailed invalidation results and removes all indexes', function () {
    [$cache] = storyblokCacheFixture();
    $uuid = '550e8400-e29b-41d4-a716-446655440001';
    $identity = new StoryblokIdentity('relations/suite', Locale::Spanish, $uuid);
    $context = new StoryblokCacheContext(Locale::Spanish, Version::Published);
    $cache->put(
        $identity,
        CachedStory::fromRelation([
            'uuid' => $uuid,
            'full_slug' => 'es/relations/suite',
        ], 1),
        $context,
        isRelation: true,
    );

    $result = $cache->invalidate(
        new StoryblokIdentity('relations/suite', Locale::Spanish),
        $context,
    );

    expect($result->payloadExisted)->toBeTrue()
        ->and($result->payloadForgotten)->toBeTrue()
        ->and($result->uuidIndexExisted)->toBeTrue()
        ->and($result->uuidIndexForgotten)->toBeTrue()
        ->and($result->slugIndexExisted)->toBeTrue()
        ->and($result->slugIndexForgotten)->toBeTrue()
        ->and($result->successful())->toBeTrue()
        ->and($cache->getByUuid($uuid, $context))->toBeNull();
});
