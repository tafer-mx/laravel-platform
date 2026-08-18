<?php

namespace TAFER\Core\Services;

use InvalidArgumentException;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoryResponse;
use TAFER\Core\Contracts\StoryblokCache;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Storyblok\CachedStory;
use TAFER\Core\Storyblok\StoryblokCacheContext;
use TAFER\Core\Storyblok\StoryblokCachePolicy;
use TAFER\Core\Storyblok\StoryblokIdentity;
use TAFER\Core\Storyblok\StoryblokRequestFactory;
use Throwable;

use function Illuminate\Support\defer;

final readonly class CachedStoryblokService implements StoryblokGateway
{
    public function __construct(
        private StoryblokGateway $origin,
        private StoryblokCache $cache,
        private StoryblokRequestFactory $requests,
        private StoryblokCachePolicy $policy,
        private Version $defaultVersion = Version::Published,
        private Locale $defaultLocale = Locale::English,
        private string $cacheNamespace = 'default',
    ) {}

    public function getStory(string $slug, ?StoryRequest $request = null): StoryResponse
    {
        $request = $this->requests->make($request, $this->defaultVersion);

        if (! $this->policy->shouldCache($request, $this->defaultVersion)) {
            return $this->origin->getStory($this->canonicalSlug($slug), $request);
        }

        $context = $this->context($request);
        $identity = new StoryblokIdentity($this->canonicalSlug($slug), $context->locale);
        $cached = $this->cache->get($identity, $context);

        if ($cached !== null) {
            return $cached->toResponse();
        }

        $response = $this->origin->getStory($identity->canonicalSlug, $request);
        $uuid = $response->story['uuid'] ?? null;
        $identity = $identity->withUuid(is_string($uuid) && $uuid !== '' ? $uuid : null);

        $this->scheduleCacheWrite($identity, $response, $context);

        return $response;
    }

    public function getStoryByUuid(string|Uuid $uuid, ?StoryRequest $request = null): StoryResponse
    {
        $request = $this->requests->make($request, $this->defaultVersion);

        if (! $this->policy->shouldCache($request, $this->defaultVersion)) {
            return $this->origin->getStoryByUuid($uuid, $request);
        }

        $uuidString = (string) $uuid;
        $context = $this->context($request);
        $cached = $this->cache->getByUuid($uuidString, $context);

        if ($cached !== null) {
            return $cached->toResponse();
        }

        $response = $this->origin->getStoryByUuid($uuid, $request);
        $identity = $this->identityFromStory($response->story, $context->locale)
            ->withUuid($uuidString);

        $this->scheduleCacheWrite($identity, $response, $context);

        return $response;
    }

    private function scheduleCacheWrite(
        StoryblokIdentity $identity,
        StoryResponse $response,
        StoryblokCacheContext $context,
    ): void {
        $name = implode(':', [
            'storyblok-cache',
            $context->namespace,
            $context->locale->value,
            $identity->canonicalSlug,
        ]);

        defer(
            function () use ($identity, $response, $context): void {
                try {
                    $this->cache->put(
                        $identity,
                        CachedStory::fromResponse($response),
                        $context,
                    );

                    foreach ($response->rels as $relation) {
                        $this->cacheRelation($relation, $response->cv, $context);
                    }
                } catch (Throwable $exception) {
                    if (function_exists('report')) {
                        report($exception);
                    }
                }
            },
            $name,
        );
    }

    /**
     * @param  array<string, mixed>  $relation
     */
    private function cacheRelation(
        array $relation,
        int $cv,
        StoryblokCacheContext $context,
    ): void {
        try {
            $identity = $this->identityFromStory($relation, $context->locale);
        } catch (InvalidArgumentException) {
            return;
        }

        if ($identity->uuid === null || $this->cache->has($identity, $context)) {
            return;
        }

        $this->cache->put(
            $identity,
            CachedStory::fromRelation($relation, $cv),
            $context,
            isRelation: true,
        );
    }

    private function context(StoryRequest $request): StoryblokCacheContext
    {
        return StoryblokCacheContext::fromRequest(
            $request,
            $this->defaultVersion,
            $this->cacheNamespace,
            $this->defaultLocale,
        );
    }

    /**
     * @param  array<string, mixed>  $story
     */
    private function identityFromStory(array $story, Locale $locale): StoryblokIdentity
    {
        $fullSlug = $story['full_slug'] ?? null;

        if (! is_string($fullSlug) || trim($fullSlug, '/') === '') {
            throw new InvalidArgumentException('The Storyblok story must contain a non-empty full_slug.');
        }

        $uuid = $story['uuid'] ?? null;

        return new StoryblokIdentity(
            canonicalSlug: $this->canonicalSlug($fullSlug),
            locale: $locale,
            uuid: is_string($uuid) && $uuid !== '' ? $uuid : null,
        );
    }

    private function canonicalSlug(string $slug): string
    {
        $segments = array_values(array_filter(
            explode('/', trim($slug, '/')),
            static fn (string $segment): bool => $segment !== '',
        ));

        if ($segments !== [] && Locale::tryFrom($segments[0]) !== null) {
            array_shift($segments);
        }

        return implode('/', $segments);
    }

    public function resolveRelation(mixed $relation, bool $draft = false, string $lang = 'en'): ?array
    {
        // Delegate to the origin service (which could be StoryblokService)
        return $this->origin->resolveRelation($relation, $draft, $lang);
    }
}
