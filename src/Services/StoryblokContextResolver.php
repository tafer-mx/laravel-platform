<?php

namespace TAFER\Core\Services;

use Illuminate\Support\Facades\Log;
use TAFER\Core\Context\RequestCtxRelation;
use TAFER\Core\Context\StoryblokBlockContext;
use TAFER\Core\Contracts\StoryblokGateway;

/**
 * Resolves and manages Storyblok context relations during component rendering.
 */
class StoryblokContextResolver
{
    public function __construct(
        private readonly StoryblokGateway $storyblok,
        private readonly RequestCtxRelation $ctxRelation,
    ) {}

    /**
     * Get the current context from the top of the stack.
     */
    public function current(): StoryblokBlockContext
    {
        return $this->ctxRelation->current();
    }

    /**
     * Push a new context onto the stack.
     */
    public function enter(StoryblokBlockContext $context): StoryblokBlockContext
    {
        return $this->ctxRelation->enter($context);
    }

    /**
     * Pop the most recent context from the stack.
     */
    public function leave(): void
    {
        $this->ctxRelation->leave();
    }

    /**
     * Resolve a context_relation from a Storyblok block.
     */
    public function resolveFromBlok(
        array $blok,
        StoryblokBlockContext $parent,
        bool $draft = false,
        string $lang = 'en',
    ): StoryblokBlockContext {
        $relation = $this->firstRelation(
            $blok['context_relation'] ?? null
        );

        if ($relation === null) {
            return $parent;
        }

        $story = $this->storyblok->resolveRelation(
            $relation,
            $draft,
            $lang,
        );

        if ($story === null) {
            Log::warning('Unable to resolve Storyblok context_relation', [
                'component' => $blok['component'] ?? null,
                'relation' => is_string($relation)
                    ? $relation
                    : ($relation['uuid'] ?? null),
            ]);

            return $parent;
        }

        return $parent->withResolvedStory($story);
    }

    /**
     * Extract the first relation from a context_relation field.
     */
    private function firstRelation(mixed $relations): mixed
    {
        if (! is_array($relations) || $relations === []) {
            return null;
        }

        return $relations[0] ?? null;
    }
}