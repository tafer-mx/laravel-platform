<?php

namespace TAFER\Core\Services;

use Illuminate\Support\Facades\Log;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoryResponse;
use Storyblok\Api\StoriesApiInterface;
use TAFER\Core\Contracts\StoryblokGateway;

class StoryblokService implements StoryblokGateway
{
    public function __construct(
        protected StoriesApiInterface $storiesApi,
    ) {}

    public function getStory(string $slug, ?StoryRequest $request = null): StoryResponse
    {
        return $this->storiesApi->bySlug($slug, $request);
    }

    public function getStoryByUuid(string|Uuid $uuid, ?StoryRequest $request = null): StoryResponse
    {
        if (is_string($uuid)) {
            $uuid = new Uuid($uuid);
        }

        return $this->storiesApi->byUuid($uuid, $request);
    }

    public function resolveRelation(mixed $relation, bool $draft = false, string $lang = 'en'): ?array
    {
        try {
            // Extract UUID from relation
            $uuid = is_string($relation) ? $relation : ($relation['uuid'] ?? null);

            if ($uuid === null) {
                return null;
            }

            // Build request with appropriate version and language
            $request = new StoryRequest(
                language: $lang,
                version: $draft ? Version::Draft : Version::Published,
            );

            // Fetch the story
            $response = $this->getStoryByUuid($uuid, $request);

            return $response->story;
        } catch (\Exception $e) {
            Log::warning('Failed to resolve Storyblok relation', [
                'relation' => $relation,
                'draft' => $draft,
                'lang' => $lang,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
