<?php

namespace TAFER\Core\Storyblok;

use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Domain\Value\Resolver\LinkType;
use Storyblok\Api\Domain\Value\Resolver\RelationCollection;
use Storyblok\Api\Domain\Value\Resolver\ResolveLinks;
use Storyblok\Api\Request\StoryRequest;
use TAFER\Core\Enums\Locale;

final readonly class StoryblokRequestFactory
{
    /**
     * @param  list<string>  $relations
     */
    public function __construct(
        private array $relations = [],
        private Locale $defaultLocale = Locale::English,
        private ?LinkType $resolveLinks = LinkType::Url,
    ) {}

    public function make(?StoryRequest $request, Version $defaultVersion): StoryRequest
    {
        $relations = $request?->withRelations;

        if ($relations === null || $relations->count() === 0) {
            $relations = new RelationCollection($this->relations);
        }

        $resolveLinks = $request?->resolveLinks;

        if ($resolveLinks === null || $resolveLinks->type === null) {
            $resolveLinks = new ResolveLinks($this->resolveLinks);
        }

        return new StoryRequest(
            language: $request?->language ?? $this->defaultLocale->value,
            version: $request?->version ?? $defaultVersion,
            withRelations: $relations,
            resolveLinks: $resolveLinks,
        );
    }
}
