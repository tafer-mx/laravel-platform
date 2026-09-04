<?php

namespace TAFER\Core\Support;

use Illuminate\Support\Str;
use TAFER\Core\Context\StoryblokBlockContext;

final class SuiteEntityHelper
{
    public const ALL_FILTER = 'all';

    public const BEDS_TAG_PREFIX = 'beds-';

    /**
     * Transforma una story cruda de Storyblok en un array normalizado para el componente.
     *
     * @return array{
     *     uuid: string|null,
     *     name: string,
     *     title: string,
     *     image: mixed,
     *     link: mixed,
     *     beds: int|null,
     *     view: string|null,
     *     amenities: list<array{label: string, icon: mixed}>,
     *     tags: list<string>
     * }
     */
    public static function fromStory(array $story): array
    {
        $context = StoryblokBlockContext::empty()->withResolvedStory($story);
        $beds = self::normalizeBeds($context->get('beds'));
        $view = self::normalizeView($context->get('view'));

        return [
            'uuid' => is_string($story['uuid'] ?? null) ? $story['uuid'] : null,
            'name' => trim((string) ($story['name'] ?? '')),
            'title' => trim((string) ($context->get('title') ?? $story['name'] ?? '')),
            'image' => $context->get('image'),
            'link' => $context->get('link'),
            'beds' => $beds,
            'view' => $view,
            'amenities' => array_values(array_filter(
                array_map(
                    static fn (array $a): array => [
                        'label' => trim((string) ($a['label'] ?? '')),
                        'icon' => $a['icon'] ?? null,
                    ],
                    array_filter((array) ($context->get('amenities') ?? []), 'is_array'),
                ),
                static fn (array $a): bool => $a['label'] !== '',
            )),
            'tags' => self::buildTags($beds, $view),
        ];
    }

    /** Formatea un número de camas como tag: 2 → 'beds-2'. */
    public static function bedsTag(int $beds): string
    {
        return self::BEDS_TAG_PREFIX.$beds;
    }

    /**
     * Genera los tags de filtrado para una suite a partir de camas y vista.
     *
     * @return list<string>
     */
    public static function buildTags(?int $beds, ?string $view): array
    {
        $tags = [];

        if ($beds !== null && $beds > 0) {
            $tags[] = self::bedsTag($beds);
        }

        if ($view !== null && $view !== '') {
            $tags[] = $view;
        }

        return $tags;
    }

    /**
     * Arma los filtros del componente: junta tags de camas/vistas, antepone "All" y asigna labels.
     *
     * @param  list<array{tags?: list<string>}>  $suites
     * @param  list<int|string>  $bedsOptions
     * @param  list<string>  $viewOptions
     * @return list<array{tag: string, label: string}>
     */
    public static function buildFilters(
        array $suites,
        callable $labelResolver,
        array $bedsOptions = [],
        array $viewOptions = [],
    ): array {
        if ($suites === []) {
            return [];
        }

        $presentTags = self::collectPresentTags($suites);
        $bedTags = self::resolveBedFilterTags($bedsOptions, $presentTags);
        $viewTags = self::resolveViewFilterTags($viewOptions, $presentTags);
        $tags = [...$bedTags, ...$viewTags];

        if ($tags === []) {
            return [];
        }

        return collect([self::ALL_FILTER])
            ->merge($tags)
            ->map(static fn (string $tag): array => [
                'tag' => $tag,
                'label' => (string) $labelResolver($tag),
            ])
            ->values()
            ->all();
    }

    /** Traduce un tag a texto legible: 'beds-2' → "2 Beds", 'ocean-view' → "Ocean View". */
    public static function resolveDefaultLabel(
        string $tag,
        string $translationPrefix = 'suites.filters',
    ): string {
        if ($tag === self::ALL_FILTER) {
            return self::translateOrHeadline("{$translationPrefix}.all", 'All');
        }

        if (preg_match('/^'.preg_quote(self::BEDS_TAG_PREFIX, '/').'(\d+)$/', $tag, $matches) === 1) {
            $count = (int) $matches[1];

            return trans_choice("{$translationPrefix}.beds", $count, ['count' => $count]);
        }

        return self::translateOrHeadline("suites.views.{$tag}", $tag);
    }

    /** Filtra stories por UUIDs respetando el orden del editor en el CMS. */
    public static function selectByUuids(array $stories, array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        $byUuid = collect($stories)->keyBy('uuid');
        $selected = [];

        foreach ($uuids as $uuid) {
            if (! is_string($uuid) || $uuid === '') {
                continue;
            }

            $story = $byUuid->get($uuid);

            if (is_array($story)) {
                $selected[] = $story;
            }
        }

        return $selected;
    }

    /** Convierte el valor crudo de camas a int positivo o null. */
    private static function normalizeBeds(mixed $beds): ?int
    {
        if ($beds === null || $beds === '') {
            return null;
        }

        if (is_numeric($beds)) {
            $normalized = (int) $beds;

            return $normalized > 0 ? $normalized : null;
        }

        return null;
    }

    /** Limpia el valor de vista a string o null. */
    private static function normalizeView(mixed $view): ?string
    {
        if (! is_string($view)) {
            return null;
        }

        $view = trim($view);

        return $view !== '' ? $view : null;
    }

    /**
     * Reúne todos los tags únicos que existen en las suites visibles.
     *
     * @param  list<array{tags?: list<string>}>  $suites
     * @return list<string>
     */
    private static function collectPresentTags(array $suites): array
    {
        return array_values(array_unique(array_merge(...array_map(
            static fn (array $suite): array => array_values(array_filter((array) ($suite['tags'] ?? []))),
            $suites,
        ))));
    }

    /**
     * Resuelve tags de camas: usa el orden del CMS si hay opciones, si no detecta automáticamente.
     *
     * @param  list<int|string>  $bedsOptions
     * @param  list<string>  $presentTags
     * @return list<string>
     */
    private static function resolveBedFilterTags(array $bedsOptions, array $presentTags): array
    {
        if ($bedsOptions !== []) {
            return collect($bedsOptions)
                ->map(static fn (mixed $value): ?string => self::normalizeBeds($value))
                ->filter()
                ->map(static fn (int $beds): string => self::bedsTag($beds))
                ->filter(static fn (string $tag): bool => in_array($tag, $presentTags, true))
                ->unique()
                ->values()
                ->all();
        }

        return collect($presentTags)
            ->filter(static fn (string $tag): bool => str_starts_with($tag, self::BEDS_TAG_PREFIX))
            ->sortBy(static fn (string $tag): int => (int) substr($tag, strlen(self::BEDS_TAG_PREFIX)))
            ->values()
            ->all();
    }

    /** Resuelve tags de vista: usa el orden del CMS si hay opciones, si no detecta automáticamente. */
    private static function resolveViewFilterTags(array $viewOptions, array $presentTags): array
    {
        $candidates = $viewOptions !== []
            ? collect($viewOptions)->map(static fn (mixed $value): ?string => self::normalizeView($value))
            : collect($presentTags)->reject(
                static fn (string $tag): bool => str_starts_with($tag, self::BEDS_TAG_PREFIX),
            );

        return $candidates
            ->filter()
            ->map(static fn (string $view): string => $view)
            ->filter(static fn (string $tag): bool => in_array($tag, $presentTags, true))
            ->unique()
            ->values()
            ->all();
    }

    /** Intenta traducir con __(); si no existe, convierte el fallback a Title Case. */
    private static function translateOrHeadline(string $key, string $fallback): string
    {
        $label = __($key);

        if ($label !== $key) {
            return $label;
        }

        return Str::of($fallback)->replace('-', ' ')->title()->toString();
    }
}
