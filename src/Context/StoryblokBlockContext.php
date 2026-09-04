<?php

namespace TAFER\Core\Context;

final class StoryblokBlockContext
{
    // Mapeo de normalización: component => [campo_origen => campo_destino]
    private const NORMALIZATION_MAP = [
        'pdf-document' => [
            'pdf.filename' => 'link',
            'pdf.alt' => 'alt_text',
        ],
        'offer_data' => [
            'general_offer_link' => 'link',
            'general_offer_title' => 'offer_title',
            'discount' => 'discount',
            'status' => 'status',
            'validity_activation_type' => 'activation',
            'validity_recurring_days' => 'date_recurring_days',
            'validity_date_time_range' => 'date_time_range',
        ],
        'suites-data' => [
            'title' => 'title',
            'image' => 'image',
            'beds' => 'beds',
            'view' => 'view',
            'suite_link' => 'link',
        ],
    ];

    private const COLLECTION_NORMALIZATION_MAP = [
        'suites-data' => [
            'amenities' => [
                'target' => 'amenities',
                'component' => 'basic-amenetie-icon',
                'map' => [
                    'image_icon.filename' => 'icon',
                    'text' => 'label',
                    'alt' => 'alt_text',
                ],
            ],
        ],
        'offer_data' => [
            'pdf-array' => [
                'target' => 'files',
                'map' => [
                    'pdf.filename' => 'link',
                    'pdf.alt' => 'alt_text',
                ],
            ],
        ],
    ];

    public function __construct(
        public readonly ?array $story = null,
        public readonly ?array $content = null,
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    public function isEmpty(): bool
    {
        return $this->story === null && $this->content === null;
    }

    public function withResolvedStory(array $story): self
    {
        $content = $story['content'] ?? null;

        if ($content !== null) {
            $content = $this->normalizeContent($content);
        }

        return new self(
            story: null,
            content: $content,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->content, $key, $default);
    }

    public function has(string $key): bool
    {
        return data_get($this->content, $key) !== null;
    }

    private function normalizeContent(array $content): array
    {
        $component = $content['component'] ?? null;

        if ($component === null) {
            return $content;
        }

        $hasNormalization = isset(self::NORMALIZATION_MAP[$component]);
        $hasCollectionNormalization = isset(self::COLLECTION_NORMALIZATION_MAP[$component]);

        if (! $hasNormalization && ! $hasCollectionNormalization) {
            return $content;
        }

        $normalized = [
            'component' => $component,
        ];

        foreach (self::NORMALIZATION_MAP[$component] ?? [] as $source => $target) {
            $value = data_get($content, $source);

            if ($value !== null) {
                $normalized[$target] = $value;
            }
        }

        foreach (self::COLLECTION_NORMALIZATION_MAP[$component] ?? [] as $source => $config) {
            $items = data_get($content, $source);

            if (! is_array($items)) {
                continue;
            }

            $normalizedItems = [];

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (
                    isset($config['component'])
                    && ($item['component'] ?? null) !== $config['component']
                ) {
                    continue;
                }

                $normalizedItem = [];

                foreach ($config['map'] as $itemSource => $itemTarget) {
                    $value = data_get($item, $itemSource);

                    if ($value !== null) {
                        $normalizedItem[$itemTarget] = $value;
                    }
                }

                if ($normalizedItem !== []) {
                    $normalizedItems[] = $normalizedItem;
                }
            }

            $normalized[$config['target']] = $normalizedItems;
        }

        return $normalized;
    }
}