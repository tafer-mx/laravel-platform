<?php

use Storyblok\Api\Domain\Value\Id;
use Storyblok\Api\Domain\Value\Uuid;
use Storyblok\Api\Request\StoriesRequest;
use Storyblok\Api\Request\StoryRequest;
use Storyblok\Api\Response\StoriesResponse;
use Storyblok\Api\Response\StoryResponse;
use Storyblok\Api\StoriesApiInterface;
use TAFER\Core\Services\StoryblokService;

function storyblokStoryResponse(array $story = []): StoryResponse
{
    return new StoryResponse([
        'story' => array_merge([
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'full_slug' => 'en/home',
            'name' => 'Home',
        ], $story),
        'cv' => 1,
        'links' => [],
    ]);
}

function fakeStoriesApi(StoryResponse $response): StoriesApiInterface
{
    return new class($response) implements StoriesApiInterface
    {
        public ?string $slug = null;

        public ?Uuid $uuid = null;

        public ?StoryRequest $request = null;

        public function __construct(
            private StoryResponse $response,
        ) {}

        public function all(?StoriesRequest $request = null): StoriesResponse
        {
            throw new BadMethodCallException('Unexpected all call.');
        }

        public function allByContentType(string $contentType, ?StoriesRequest $request = null): StoriesResponse
        {
            throw new BadMethodCallException('Unexpected allByContentType call.');
        }

        public function allByUuids(array $uuids, bool $keepOrder = true, ?StoriesRequest $request = null): StoriesResponse
        {
            throw new BadMethodCallException('Unexpected allByUuids call.');
        }

        public function bySlug(string $slug, ?StoryRequest $request = null): StoryResponse
        {
            $this->slug = $slug;
            $this->request = $request;

            return $this->response;
        }

        public function byUuid(Uuid $uuid, ?StoryRequest $request = null): StoryResponse
        {
            $this->uuid = $uuid;
            $this->request = $request;

            return $this->response;
        }

        public function byId(Id $id, ?StoryRequest $request = null): StoryResponse
        {
            throw new BadMethodCallException('Unexpected byId call.');
        }
    };
}

it('gets a story by slug', function () {
    $response = storyblokStoryResponse(['full_slug' => 'en/special-offers']);
    $storiesApi = fakeStoriesApi($response);
    $request = new StoryRequest(language: 'en');
    $service = new StoryblokService($storiesApi);

    $story = $service->getStory('en/special-offers', $request);

    expect($story)->toBe($response)
        ->and($storiesApi->slug)->toBe('en/special-offers')
        ->and($storiesApi->request)->toBe($request);
});

it('gets a story by uuid string', function () {
    $response = storyblokStoryResponse();
    $storiesApi = fakeStoriesApi($response);
    $request = new StoryRequest(language: 'es');
    $service = new StoryblokService($storiesApi);

    $story = $service->getStoryByUuid('550e8400-e29b-41d4-a716-446655440000', $request);

    expect($story)->toBe($response)
        ->and((string) $storiesApi->uuid)->toBe('550e8400-e29b-41d4-a716-446655440000')
        ->and($storiesApi->request)->toBe($request);
});

it('gets a story by uuid value object', function () {
    $response = storyblokStoryResponse();
    $storiesApi = fakeStoriesApi($response);
    $uuid = new Uuid('550e8400-e29b-41d4-a716-446655440000');
    $service = new StoryblokService($storiesApi);

    $story = $service->getStoryByUuid($uuid);

    expect($story)->toBe($response)
        ->and($storiesApi->uuid)->toBe($uuid)
        ->and($storiesApi->request)->toBeNull();
});
