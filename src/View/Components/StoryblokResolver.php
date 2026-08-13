<?php

namespace TAFER\Core\View\Components;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View as ViewContract;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class StoryblokResolver extends Component
{
    /**
     * @param  array<int, mixed>  $bloks
     * @param  list<string>  $paths
     */
    public function __construct(
        public array $bloks = [],
        public array $paths = [
            'components.storyblok.basic.',
            'components.storyblok.elements.',
            'components.storyblok.sections.',
            'components.storyblok.pages.',
            'components.storyblok.layout.',
            'components.storyblok.references.',
            'components.storyblok.',
            'components.',
        ],
        public mixed $globalConfig = null,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        return array_values(array_filter(
            $this->bloks,
            static fn (mixed $blok): bool => is_array($blok)
                && trim((string) ($blok['component'] ?? '')) !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $blok
     */
    public function resolveDynamicComponent(array $blok): ?string
    {
        $componentName = (string) $blok['component'];

        if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $componentName) !== 1) {
            return null;
        }

        foreach ($this->paths as $path) {
            $name = Str::after($path, 'components.').$componentName;

            if (View::exists('components.'.str_replace('.', '/', $name))) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function candidates(string $componentName): array
    {
        return [
            ...array_map(
                static fn (string $path): string => $path.$componentName,
                $this->paths,
            ),
            'components.storyblok._unknown',
        ];
    }

    public function render(): ViewContract
    {
        return view('tafer::components.storyblok-resolver');
    }
}
