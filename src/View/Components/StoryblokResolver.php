<?php

namespace TAFER\Core\View\Components;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View as ViewContract;
use TAFER\Core\Services\StoryblokContextResolver;

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
        public mixed $tafer_rewards_mode = false,
        public mixed $footer_mode = false,
        public mixed $preserve_original_colors = false,
        public mixed $footer_logo = false,
        public mixed $mobile_full_width = false,
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
     * Resolve and enter the context created by this block.
     *
     * Returns true only when the block created its own context.
     *
     * @param  array<string, mixed>  $blok
     */
    public function enterContext(
        array $blok,
        bool $draft = false,
        string $lang = 'en',
    ): bool {
        $contextResolver = app(StoryblokContextResolver::class);

        $parentContext = $contextResolver->current();

        $currentContext = $contextResolver->resolveFromBlok(
            $blok,
            $parentContext,
            $draft,
            $lang,
        );

        $createdContext = $currentContext !== $parentContext;

        if ($createdContext) {
            $contextResolver->enter($currentContext);
        }

        return $createdContext;
    }

    /**
     * Remove only the context created by the current block.
     */
    public function leaveContext(bool $createdContext): void
    {
        if (! $createdContext) {
            return;
        }

        app(StoryblokContextResolver::class)->leave();
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