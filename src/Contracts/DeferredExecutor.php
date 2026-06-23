<?php

namespace TAFER\Core\Contracts;

use Closure;

interface DeferredExecutor
{
    public function execute(string $name, Closure $callback): void;
}
