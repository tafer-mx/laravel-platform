<?php

namespace TAFER\Core\Services;

use Illuminate\Support\Facades\Log;
use TAFER\Core\Contracts\StoryblokGateway;
use TAFER\Core\Context\RequestCtxRelation;
use TAFER\Core\Context\StoryblokBlockContext;

/**
 * Resolves and manages Storyblok context relations during component rendering.
 *
 * This service handles the resolution of context_relation fields in Storyblok
 * components and manages the context stack through RequestCtxRelation. When a
 * component has a context_relation field, this resolver fetches the referenced
 * story and creates a new context that can be used by child components.
 *
 * Flow:
 * 1. Component with context_relation is encountered
 * 2. resolveFromBlok() extracts the relation UUID
 * 3. Relation is resolved to a full story via StoryblokGateway
 * 4. New context is created from the story content
 * 5. Context is pushed onto the stack via RequestCtxRelation
 * 6. Child components can access the context
 * 7. When component rendering completes, context is popped from stack
 *
 * Usage in Controllers:
 * ```php
 * $contextResolver = app(StoryblokContextResolver::class);
 * $context = $contextResolver->resolveFromBlok(
 *     $blok,
 *     StoryblokBlockContext::empty(),
 *     $isPreview,
 *     $locale
 * );
 * $contextResolver->enter($context);
 * ```
 *
 * Usage in Components:
 * ```php
 * $context = app(StoryblokContextResolver::class)->current();
 * $value = $context->get('field_name');
 * ```
 *
 * @see StoryblokBlockContext for the context data structure
 * @see RequestCtxRelation for the context stack management
 * @see StoryblokGateway for story fetching
 */
class StoryblokContextResolver
{
    public function __construct(
        private readonly StoryblokGateway $storyblok,
        private readonly RequestCtxRelation $ctxRelation,
    ) {}

    /**
     * Get the current context from the top of the stack.
     *
     * @return StoryblokBlockContext The current context, or empty if stack is empty
     */
    public function current(): StoryblokBlockContext
    {
        return $this->ctxRelation->current();
    }

    /**
     * Push a new context onto the stack.
     *
     * @param  StoryblokBlockContext  $context  The context to push
     * @return StoryblokBlockContext The same context (for chaining)
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
     *
     * If the block has a context_relation field, this method will:
     * 1. Extract the first relation (UUID or relation object)
     * 2. Fetch the referenced story from Storyblok
     * 3. Create a new context with the resolved story
     * 4. Return the new context (or parent context if resolution fails)
     *
     * @param  array  $blok  The Storyblok block data
     * @param  StoryblokBlockContext  $parent  The parent context to inherit from if resolution fails
     * @param  bool  $draft  Whether to fetch draft or published version
     * @param  string  $lang  The language code
     * @return StoryblokBlockContext The resolved context or parent context
     */
    public function resolveFromBlok(
        array $blok,
        StoryblokBlockContext $parent,
        bool $draft = false,
        string $lang = 'en',
    ): StoryblokBlockContext {
        $relation = $this->firstRelation($blok['context_relation'] ?? null);

        if ($relation === null) {
            return $parent;
        }

        $story = $this->storyblok->resolveRelation($relation, $draft, $lang);

        if ($story === null) {
            Log::warning('Unable to resolve Storyblok context_relation', [
                'component' => $blok['component'] ?? null,
                'relation' => is_string($relation) ? $relation : ($relation['uuid'] ?? null),
            ]);

            return $parent;
        }

        return $parent->withResolvedStory($story);
    }

    /**
     * Extract the first relation from a context_relation field.
     *
     * @param  mixed  $relations  The context_relation field value
     * @return mixed The first relation (UUID string or relation object) or null
     */
    private function firstRelation(mixed $relations): mixed
    {
        if (! is_array($relations) || $relations === []) {
            return null;
        }

        return $relations[0] ?? null;
    }
}
