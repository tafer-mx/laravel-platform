<?php

    namespace TAFER\Core\Services;

    use Illuminate\Support\Facades\Log;
    use Storyblok\Api\Domain\Value\Dto\Version;
    use Storyblok\Api\Domain\Value\Uuid;
    use Storyblok\Api\Request\StoryRequest;
    use Storyblok\Api\Response\StoryResponse;
    use Storyblok\Api\StoriesApiInterface;
    use TAFER\Core\Contracts\StoryblokGateway;
    use Illuminate\Support\Str;

    class StoryblokService implements StoryblokGateway
    {
        public function __construct(
            protected StoriesApiInterface $storiesApi,
        ) {}

        protected function ctx(): RequestCtx
        {
            return app(RequestCtx::class);
        }

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

        public function resolveRelation(
            mixed $relation,
            bool $draft = false,
            string $lang = 'en',
        ): ?array {
            if (is_array($relation) && isset($relation['content'])) {
                return $relation;
            }

            $uuid = is_string($relation)
                ? $relation
                : ($relation['uuid'] ?? null);

            if (! is_string($uuid) || ! Str::isUuid($uuid)) {
                return null;
            }

            try {
                $request = new StoryRequest(
                    language: $lang,
                    version: $draft ? Version::Draft : Version::Published,
                );

                $response = $this->getStoryByUuid($uuid, $request);

                return $response->story;
            } catch (\Throwable $e) {
                Log::warning('Failed to resolve Storyblok relation', [
                    'relation' => $relation,
                    'draft' => $draft,
                    'lang' => $lang,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        public function getStoriesByContentType(string $contentType, array $filters = [], bool $draft = false, string $lang = 'en'): array
        {
            $filterCollection = new FilterCollection();

            foreach ($filters as $filter) {
                $filterCollection->add($filter);
            }

            $request = new StoriesRequest(
                language: $lang,
                filters: $filterCollection,
                version: $draft ? Version::Draft : Version::Published,
            );

            return $this->storiesApi->allByContentType($contentType, $request)->stories;
        }

        private function request(bool $draft, string $lang): StoryRequest
        {
            return new StoryRequest(
                language: $lang,
                version: $draft ? Version::Draft : Version::Published,
            );
        }

    }
