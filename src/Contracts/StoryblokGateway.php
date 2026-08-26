<?php

namespace TAFER\Core\Contracts;

use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoriesRequest;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoriesResponse;
use Storyblok\Api\Response\StoryResponse;

interface StoryblokGateway
{
    public function getStory(string $slug, ?StoryRequest $request = null): StoryResponse;

    public function getStoryByUuid(string|Uuid $uuid, ?StoryRequest $request = null): StoryResponse;

    public function resolveRelation(mixed $relation, bool $draft = false, string $lang = 'en'): ?array;

    public function getStoriesByContentType(string $contentType, ?StoriesRequest $request = null): StoriesResponse;
}