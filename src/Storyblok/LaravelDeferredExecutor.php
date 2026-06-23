<?php

namespace TAFER\Core\Storyblok;

use Closure;
use TAFER\Core\Contracts\DeferredExecutor;

use function Illuminate\Support\defer;

final class LaravelDeferredExecutor implements DeferredExecutor
{
    public function execute(string $name, Closure $callback): void
    {
        defer($callback, $name);
    }
}
