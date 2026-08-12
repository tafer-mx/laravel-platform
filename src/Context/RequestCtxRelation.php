<?php

namespace TAFER\Core\Context;

/**
 * Request-scoped stack of Storyblok context relations.
 *
 * This service manages a stack of StoryblokBlockContext instances that represent
 * the current context hierarchy during component rendering. Unlike RequestCtx
 * (which holds immutable request information), RequestCtxRelation maintains
 * a mutable stack that changes as components with context_relation fields are
 * entered and exited.
 *
 * Lifecycle:
 * - Registered as 'scoped' in the service container (resets per HTTP request)
 * - Stack starts empty at the beginning of each request
 * - Components with context_relation push new contexts onto the stack
 * - Contexts are popped when component rendering completes
 *
 * @see StoryblokBlockContext for the immutable context data structure
 * @see StoryblokContextResolver for the service that populates this stack
 */
final class RequestCtxRelation
{
    /** @var list<StoryblokBlockContext> */
    private array $stack = [];

    /**
     * Get the current context from the top of the stack.
     *
     * @return StoryblokBlockContext The current context, or an empty context if stack is empty
     */
    public function current(): StoryblokBlockContext
    {
        if ($this->stack === []) {
            return StoryblokBlockContext::empty();
        }

        return $this->stack[array_key_last($this->stack)];
    }

    /**
     * Push a new context onto the stack.
     *
     * @param StoryblokBlockContext $context The context to push
     * @return StoryblokBlockContext The same context (for chaining)
     */
    public function enter(StoryblokBlockContext $context): StoryblokBlockContext
    {
        $this->stack[] = $context;

        return $context;
    }

    /**
     * Pop the most recent context from the stack.
     *
     * Safe to call even if stack is empty (no-op).
     */
    public function leave(): void
    {
        if ($this->stack !== []) {
            array_pop($this->stack);
        }
    }

    /**
     * Clear the entire stack.
     *
     * Useful for testing or manual cleanup, though Laravel's scoped
     * binding should handle cleanup automatically between requests.
     */
    public function reset(): void
    {
        $this->stack = [];
    }

    /**
     * Get the current stack depth.
     *
     * @return int Number of contexts on the stack
     */
    public function depth(): int
    {
        return count($this->stack);
    }

    /**
     * Check if the stack is empty.
     *
     * @return bool True if no contexts are on the stack
     */
    public function isEmpty(): bool
    {
        return $this->stack === [];
    }
}
