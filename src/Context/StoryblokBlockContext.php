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
        // Agregar más mapeos según sea necesario
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

        // Normalizar contenido si es necesario
        if ($content !== null) {
            $content = $this->normalizeContent($content);
        }

        return new self(
            story: null, // No necesitamos el story completo, solo el content
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

    /**
     * Normaliza el contenido según el tipo de componente
     */
    private function normalizeContent(array $content): array
    {
        $component = $content['component'] ?? null;

        if ($component === null || ! isset(self::NORMALIZATION_MAP[$component])) {
            return $content;
        }

        // Crear un nuevo array solo con los campos normalizados
        $normalized = [
            'component' => $component,
        ];

        foreach (self::NORMALIZATION_MAP[$component] as $source => $target) {
            $value = data_get($content, $source);

            if ($value !== null) {
                $normalized[$target] = $value;
            }
        }

        return $normalized;
    }
}
