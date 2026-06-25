<?php

namespace TAFER\Core\Storyblok;

final readonly class StoryblokInvalidationResult
{
    public function __construct(
        public bool $payloadExisted,
        public bool $payloadForgotten,
        public bool $uuidIndexExisted,
        public bool $uuidIndexForgotten,
        public bool $slugIndexExisted,
        public bool $slugIndexForgotten,
    ) {}

    public function successful(): bool
    {
        return (! $this->payloadExisted || $this->payloadForgotten)
            && (! $this->uuidIndexExisted || $this->uuidIndexForgotten)
            && (! $this->slugIndexExisted || $this->slugIndexForgotten);
    }
}
