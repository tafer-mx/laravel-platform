<?php

namespace TAFER\Core\Contracts;

use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoryResponse;

interface StoryblokGateway
{
    public function getStory(string $slug, ?StoryRequest $request = null): StoryResponse;

    public function getStoryByUuid(string|Uuid $uuid, ?StoryRequest $request = null): StoryResponse;

    /**
     * Resolve a Storyblok relation to get the referenced story data.
     *
     * @param  mixed  $relation  UUID string or array with 'uuid' key
     * @param  bool  $draft  Whether to fetch draft or published version
     * @param  string  $lang  The language code
     * @return array|null The story data array or null if resolution fails
     */
    public function resolveRelation(mixed $relation, bool $draft = false, string $lang = 'en'): ?array;
}
