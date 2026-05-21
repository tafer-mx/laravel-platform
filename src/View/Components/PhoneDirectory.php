<?php

namespace TAFER\Core\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use TAFER\Core\Enums\Resort;

class PhoneDirectory extends Component
{
    public function __construct(
        public Resort $resort
    ) {}

    public function render(): View
    {
        return view('tafer::components.phone-directory');
    }
}