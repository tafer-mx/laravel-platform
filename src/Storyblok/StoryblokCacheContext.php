<?php

namespace TAFER\Core\Storyblok;

use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Request\StoryRequest;
use TAFER\Core\Enums\Locale;

final readonly class StoryblokCacheContext
{
    public function __construct(
        public Locale $locale,
        public Version $version,
        public string $namespace = 'default',
    ) {}

    public static function fromRequest(
        ?StoryRequest $request,
        Version $defaultVersion,
        string $namespace = 'default',
        Locale $defaultLocale = Locale::English,
    ): self {
        return new self(
            locale: Locale::tryFrom($request?->language ?? '') ?? $defaultLocale,
            version: $request?->version ?? $defaultVersion,
            namespace: $namespace,
        );
    }
}
