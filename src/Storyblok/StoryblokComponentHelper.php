<?php

namespace TAFER\Core\Storyblok;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class StoryblokComponentHelper
{
    /**
     * Get the value of a field from the first matching component
     * inside a Storyblok story Body.
     *
     * If the component or field is not found, null is returned.
     *
     * @param  array  $story  Storyblok story array (reference resolved)
     * @param  string  $component  Component name to search for
     * @param  string  $field  Field name to extract from the component
     * @return mixed|null
     */
    public static function getFirstField(array $story, string $component, string $field): mixed
    {
        $root = $story['content'] ?? $story;   // por si a veces mandas content ya “plano”
        $blok = self::findComponent($root, $component);

        return $blok[$field] ?? null;
    }

    /**
     * Recursively find the first Storyblok block
     * matching the given component name.
     *
     * @param  array  $blocks  Array of Storyblok blocks
     * @param  string  $component  Component name to search for
     */
    public static function findComponent(array $data, string $component): ?array
    {
        // Si este nodo ya es un blok
        if (($data['component'] ?? null) === $component) {
            return $data;
        }

        // Recorrer TODOS los hijos que sean arrays (Title, Description, Body, etc.)
        foreach ($data as $value) {
            if (is_array($value)) {
                // Si es lista de bloques o un bloque asociativo, igual funciona
                $found = self::findComponent($value, $component);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Agrega datos dinámicos a un bloque específico de Storyblok de forma recursiva
     *
     * @param  array  &$blocks  Array de bloques de Storyblok
     * @param  string  $component  Nombre del componente donde agregar los datos
     * @param  string  $key  Nombre de la propiedad a agregar
     * @param  mixed  $value  Valor a agregar (puede ser array, string, object, etc.)
     */
    public static function addDataToBlok(array &$data, string $component, string $key, $value): void
    {
        if (array_is_list($data)) {
            foreach ($data as &$item) {
                if (is_array($item)) {
                    self::addDataToBlok($item, $component, $key, $value);
                }
            }

            return;
        }

        if (($data['component'] ?? null) === $component) {
            $data[$key] = $value;
        }

        foreach ($data as &$child) {
            if (is_array($child)) {
                self::addDataToBlok($child, $component, $key, $value);
            }
        }
    }

    /**
     * Normaliza el valor de columnas recibido desde Storyblok.
     *
     * Storyblok puede devolver el campo vacío, null o con una estructura inesperada.
     * Este método garantiza que el Blade siempre trabaje con un array.
     */
    public static function normalizeColumns(mixed $columns): array
    {
        return is_array($columns) ? $columns : [];
    }

    /**
     * Obtiene las clases Tailwind necesarias para renderizar columnas
     * en orientación horizontal o vertical.
     *
     * Si Storyblok envía un valor inválido, se usa vertical como fallback.
     *
     * @return array{wrapper: string, item: string}
     */
    public static function getColumnOrientationClasses(?string $orientation): array
    {
        $orientation = in_array($orientation, ['horizontal', 'vertical'], true)
            ? $orientation
            : 'vertical';

        return [
            'wrapper' => $orientation === 'horizontal'
                ? 'flex-row flex-wrap'
                : 'flex-col',

            'item' => $orientation === 'horizontal'
                ? 'w-auto'
                : 'w-full',
        ];
    }

    /**
     * Detecta si dentro de las columnas existe al menos un componente Basic_icon
     * con label definido.
     *
     * Este caso se usa para forzar una presentación vertical en los iconos,
     * evitando problemas de alineación cuando se combinan iconos y texto.
     */
    public static function hasBasicIconsWithLabels(array $columns): bool
    {
        foreach ($columns as $column) {
            if (($column['component'] ?? '') === 'Basic_icon' && ! empty($column['label'] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitiza clases Tailwind de gap recibidas desde Storyblok.
     *
     * Solo se permiten utilidades de espaciado gap, gap-x y gap-y con valores
     * existentes en la escala estándar de Tailwind.
     * https://tailwindcss.com/docs/gap
     *
     * Ejemplos permitidos:
     * - gap-4
     * - gap-x-4
     * - gap-y-4
     *
     * Ejemplos rechazados:
     * - text-xl
     * - bg-red-500
     * - absolute
     * - custom-class
     *
     * Esto evita renderizar clases arbitrarias desde el CMS y mantiene
     * controlado el diseño del componente.
     */
    public static function sanitizeGapClasses(?string $value, string $default = 'gap-4'): string
    {
        $tokens = preg_split('/\s+/', trim((string) $value)) ?: [];

        $safeTokens = [];

        foreach ($tokens as $token) {
            if (preg_match('/^gap(-x|-y)?-(0|px|0\.5|1|1\.5|2|2\.5|3|3\.5|4|5|6|7|8|9|10|11|12|14|16|20|24|28|32|36|40|44|48|52|56|60|64|72|80|96)$/', $token)) {
                $safeTokens[] = $token;
            }
        }

        return ! empty($safeTokens)
            ? implode(' ', $safeTokens)
            : $default;
    }
}
