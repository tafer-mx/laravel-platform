<?php

namespace TAFER\Core\Services;

use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoryResponse;
use Storyblok\Api\StoriesApiInterface;

class StoryblokService
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
}
