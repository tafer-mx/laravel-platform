<?php

    namespace TAFER\Core\Storyblok\Services;

    use Storyblok\Api\StoryblokClient;
    use Storyblok\Api\StoriesApi;
    use Storyblok\Api\Domain\Value\Dto\Version;
    use Storyblok\Api\Request\StoryRequest;
    use Storyblok\Api\Domain\Value\Resolver\{LinkType, ResolveLinks};
    use Storyblok\Api\Domain\Value\Uuid;
    use Storyblok\Api\Response\StoryResponse;
    use Storyblok\Api\Domain\Value\Resolver\RelationCollection;

    use TAFER\Core\Enums\Locale;
    use TAFER\Core\Repositories\StoryCacheRepository;

    //TODO: Validar version draft
    class StoryblokContentService
    {
        private readonly StoriesApi $api;

        public function __construct(
            private readonly StoryblokClient $client,
            private readonly StoryCacheRepository $cache,
            private readonly array $resolveReferences = [],
        ) {
            $this->api = new StoriesApi($this->client);
        }

        public function getStory(string $slug, ?Version $version, ?Locale $locale): StoryResponse
        {
            $version ??= Version::Published;
            $locale  ??= Locale::English;

            $normalizedSlug = ltrim($slug, '/');
            $cachedCv       = $this->cache->getCv($slug);

            $response = $this->fetchWithCv($normalizedSlug, $version, $locale, $cachedCv);

            // Cache miss o story vacía: fetch completo con relations
            if (!$response?->story) {
                $response = $this->fetchFull($normalizedSlug, $version, $locale);
            }

            if ($response->cv !== null) {
                $this->cache->putCv($slug, $response->cv);
            }

            return $response;
        }

        public function getStoryByUuid(string|Uuid $uuid, ?Version $version, ?Locale $locale): StoryResponse
        {
            $version ??= Version::Published;
            $locale  ??= Locale::English;

            $request = new StoryRequest(
                language: $locale->value,
                version: $version,
                resolveLinks: new ResolveLinks(LinkType::Url),
                withRelations: new RelationCollection($this->resolveReferences)
            );

            return $this->api->byUuid($uuid, $request);
        }

        //TODO: Crear issue en https://github.com/storyblok/php-content-api-client/issues
        // Para que el cv pueda ser mandado en StoryRequest y así evitar implementacion manual
        // O crear abstraccion para mejorar logica de peticion manual
        private function fetchWithCv(
            string $slug,
            Version $version,
            Locale $locale,
            ?string $cv
        ): ?StoryResponse {
            if ($cv === null) {
                return null;
            }

            $raw = $this->client->request('GET', "stories/{$slug}", [
                'query' => [
                    'language'      => $locale->value,
                    'version'       => $version->value,
                    'resolve_links' => 'url',
                    'cv'            => $cv,
                ],
            ]);

            return new StoryResponse($raw->toArray());
        }

        private function fetchFull(string $slug, Version $version, Locale $locale): StoryResponse
        {
            $raw = $this->client->request('GET', "stories/{$slug}", [
                'query' => [
                    'language'          => $locale->value,
                    'version'           => $version->value,
                    'resolve_links'     => 'url',
                    'resolve_relations' => implode(',', $this->resolveReferences),
                ],
            ]);

            return new StoryResponse($raw->toArray());
        }
    }