<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Storyblok\StoryblokRequestFactory;

final readonly class StoryblokPageController
{
    public function __construct(
        private StoryblokGateway $storyblok,
        private StoryblokRequestFactory $requests,
    ) {}

    public function __invoke(RequestCtx $requestContext): JsonResponse
    {
        $response = $this->storyblok->getStory(
            $requestContext->storyblokSlug(),
            $requestContext->storyblokRequest($this->requests),
        );

        return response()->json([
            'request_context' => [
                'resort' => $requestContext->resort->value,
                'locale' => $requestContext->locale->value,
                'location' => $requestContext->location->value,
                'public_slug' => $requestContext->slug,
                'storyblok_slug' => $requestContext->storyblokSlug(),
                'preview' => $requestContext->isPreview,
                'device' => $requestContext->device->value,
            ],
            'story' => $response->story,
            'relations' => $response->rels,
        ]);
    }
}
