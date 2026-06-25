<?php

namespace TAFER\Core\Storyblok;

final readonly class StoryblokCacheKey
{
    public function __construct(
        private string $prefix = 'tafer:storyblok',
    ) {}

    public function payload(StoryblokIdentity $identity, StoryblokCacheContext $context): string
    {
        return $this->make('story', $identity->canonicalSlug, $context);
    }

    public function uuid(string $uuid, StoryblokCacheContext $context): string
    {
        return $this->make('uuid', strtolower($uuid), $context);
    }

    public function slugUuid(StoryblokIdentity $identity, StoryblokCacheContext $context): string
    {
        return $this->make('slug-uuid', $identity->canonicalSlug, $context);
    }

    private function make(string $type, string $identifier, StoryblokCacheContext $context): string
    {
        $segments = [
            trim($this->prefix, ':'),
            $this->segment($context->namespace),
            $context->version->value,
            $context->locale->value,
            $type,
            hash('sha256', trim($identifier, '/')),
        ];

        return implode(':', $segments);
    }

    private function segment(string $value): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_.-]+/', '-', trim($value));

        return trim($normalized ?: 'default', '-');
    }
}
