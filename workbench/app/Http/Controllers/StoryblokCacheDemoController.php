<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoryResponse;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Services\CachedStoryblokService;
use TAFER\Core\Storyblok\ImmediateDeferredExecutor;
use TAFER\Core\Storyblok\LaravelStoryblokCache;
use TAFER\Core\Storyblok\StoryblokCacheKey;
use TAFER\Core\Storyblok\StoryblokCachePolicy;
use TAFER\Core\Storyblok\StoryblokPath;
use TAFER\Core\Storyblok\StoryblokRequestFactory;
use TAFER\Core\Storyblok\StoryblokSlugNormalizer;
use TAFER\Core\Storyblok\StoryblokWebhookInvalidator;

final class StoryblokCacheDemoController
{
    public function __invoke(): JsonResponse
    {
        $parentSlug = StoryblokPath::forResort(
            Resort::HotelMousai,
            Location::PuertoVallarta,
            'suites',
        );
        $relationSlug = StoryblokPath::join($parentSlug, 'north-suite');
        $relationUuid = '550e8400-e29b-41d4-a716-446655440001';
        $origin = $this->fakeOrigin($parentSlug, $relationSlug, $relationUuid);
        $cache = new LaravelStoryblokCache(
            cache: new Repository(new ArrayStore),
            keys: new StoryblokCacheKey('workbench:storyblok'),
        );
        $normalizer = new StoryblokSlugNormalizer;
        $service = new CachedStoryblokService(
            origin: $origin,
            cache: $cache,
            deferred: new ImmediateDeferredExecutor,
            normalizer: $normalizer,
            requests: new StoryblokRequestFactory([
                'reference_suite-v1_global.suite',
            ]),
            policy: new StoryblokCachePolicy,
            cacheNamespace: 'workbench',
        );
        $request = new StoryRequest(language: Locale::Spanish->value);

        $firstParent = $service->getStory("es/{$parentSlug}", $request);
        $secondParent = $service->getStory($parentSlug, $request);
        $cachedRelation = $service->getStoryByUuid($relationUuid, $request);

        $invalidator = new StoryblokWebhookInvalidator(
            cache: $cache,
            normalizer: $normalizer,
            cacheNamespace: 'workbench',
        );
        $invalidation = $invalidator->invalidate(
            fullSlug: "es/{$relationSlug}",
            locale: Locale::Spanish,
            uuid: $relationUuid,
        );

        $freshRelation = $service->getStoryByUuid($relationUuid, $request);

        return response()->json([
            'scenario' => [
                'locale' => Locale::Spanish->value,
                'resort' => Resort::HotelMousai->value,
                'location' => Location::PuertoVallarta->value,
                'canonical_parent_slug' => $parentSlug,
                'canonical_relation_slug' => $relationSlug,
            ],
            'flow' => [
                'first_parent' => [
                    'source' => 'fake Storyblok API',
                    'name' => $firstParent->story['name'],
                ],
                'second_parent' => [
                    'source' => 'cache',
                    'name' => $secondParent->story['name'],
                ],
                'relation_before_webhook' => [
                    'source' => 'cache populated from parent rels',
                    'name' => $cachedRelation->story['name'],
                ],
                'webhook_invalidation' => [
                    'successful' => $invalidation->successful(),
                    'payload_forgotten' => $invalidation->payloadForgotten,
                    'uuid_index_forgotten' => $invalidation->uuidIndexForgotten,
                    'slug_index_forgotten' => $invalidation->slugIndexForgotten,
                ],
                'relation_after_webhook' => [
                    'source' => 'fake Storyblok API after cache miss',
                    'name' => $freshRelation->story['name'],
                ],
            ],
            'origin_calls' => [
                'by_slug' => $origin->slugCalls,
                'by_uuid' => $origin->uuidCalls,
            ],
            'expected' => [
                'by_slug' => 1,
                'by_uuid' => 1,
            ],
        ]);
    }

    private function fakeOrigin(
        string $parentSlug,
        string $relationSlug,
        string $relationUuid,
    ): StoryblokGateway {
        $relation = [
            'uuid' => $relationUuid,
            'full_slug' => "es/{$relationSlug}",
            'name' => 'North Suite',
        ];
        $parent = new StoryResponse([
            'story' => [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'full_slug' => "es/{$parentSlug}",
                'name' => 'Suites',
            ],
            'cv' => 1,
            'rels' => [$relation],
            'rel_uuids' => [$relationUuid],
            'links' => [],
        ]);
        $relationResponse = new StoryResponse([
            'story' => $relation,
            'cv' => 2,
            'rels' => [],
            'rel_uuids' => [],
            'links' => [],
        ]);

        return new class($parent, $relationResponse) implements StoryblokGateway
        {
            public int $slugCalls = 0;

            public int $uuidCalls = 0;

            public function __construct(
                private StoryResponse $parent,
                private StoryResponse $relation,
            ) {}

            public function getStory(string $slug, ?StoryRequest $request = null): StoryResponse
            {
                $this->slugCalls++;

                return $this->parent;
            }

            public function getStoryByUuid(
                string|Uuid $uuid,
                ?StoryRequest $request = null,
            ): StoryResponse {
                $this->uuidCalls++;

                return $this->relation;
            }
        };
    }
}
