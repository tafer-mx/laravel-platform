<?php

namespace TAFER\Core\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use TAFER\Core\Enums\Resort;

class HelloWorld extends Component
{
    public Resort $resort;

    /**
     * Create a new component instance.
     */
    public function __construct(Resort $resort)
    {
        $this->resort = $resort;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('tafer::hello-world');
    }
}
