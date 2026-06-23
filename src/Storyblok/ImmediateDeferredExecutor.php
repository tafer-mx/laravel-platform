<?php

namespace TAFER\Core\Storyblok;

use Closure;
use TAFER\Core\Contracts\DeferredExecutor;

final class ImmediateDeferredExecutor implements DeferredExecutor
{
    public function execute(string $name, Closure $callback): void
    {
        $callback();
    }
}
