<?php

namespace TAFER\Core\Storyblok;

use TAFER\Core\Enums\Locale;

final readonly class StoryblokIdentity
{
    public function __construct(
        public string $canonicalSlug,
        public Locale $locale,
        public ?string $uuid = null,
    ) {}

    public function withUuid(?string $uuid): self
    {
        return new self($this->canonicalSlug, $this->locale, $uuid);
    }
}
