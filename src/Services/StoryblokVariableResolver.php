<?php

namespace TAFER\Core\Services;

use TAFER\Core\Context\StoryblokBlockContext;

class StoryblokVariableResolver
{
    /**
     * Patrón para detectar variables: {{ variable_name }}
     * Solo permite: letras, números, guion, guion bajo, punto
     */
    private const VARIABLE_PATTERN = '/\{\{\s*([a-zA-Z0-9_.\-]+)\s*\}\}/';

    /**
     * Separadores que deben eliminarse si quedan huérfanos
     */
    private const ORPHAN_SEPARATORS = ['-', '–', '—', '|', '/'];

    /**
     * Resuelve variables en un texto usando el contexto de Storyblok
     *
     * @param  string|null  $text  Texto con variables {{ variable }}
     * @param  StoryblokBlockContext|null  $context  Contexto actual del componente
     * @return string Texto con variables reemplazadas
     */
    public function resolve(?string $text, ?StoryblokBlockContext $context): string
    {
        // Si el texto es null o vacío, devolver string vacío
        if ($text === null || $text === '') {
            return '';
        }

        // Si no hay contexto o está vacío, solo limpiar las variables
        if ($context === null || $context->isEmpty()) {
            return $this->cleanupText($this->removeAllVariables($text));
        }

        // Reemplazar cada variable por su valor del contexto
        $resolved = preg_replace_callback(
            self::VARIABLE_PATTERN,
            function ($matches) use ($context) {
                $variableName = $matches[1];
                $value = $context->get($variableName);

                // Solo usar valores escalares (string, int, float, bool)
                if ($value === null || ! is_scalar($value)) {
                    return '';
                }

                // Convertir a string
                return (string) $value;
            },
            $text
        );

        // Limpiar el resultado
        return $this->cleanupText($resolved);
    }

    /**
     * Elimina todas las variables del texto sin reemplazarlas
     */
    private function removeAllVariables(string $text): string
    {
        return preg_replace(self::VARIABLE_PATTERN, '', $text);
    }

    /**
     * Limpia el texto después del reemplazo de variables
     */
    private function cleanupText(string $text): string
    {
        // 1. Normalizar espacios múltiples a uno solo
        $text = preg_replace('/\s+/', ' ', $text);

        // 2. Eliminar espacios antes de signos de puntuación comunes
        $text = preg_replace('/\s+([.,;:!?])/', '$1', $text);

        // 3. Eliminar separadores huérfanos al inicio o final
        $text = $this->removeOrphanSeparators($text);

        // 4. Eliminar separadores huérfanos duplicados o adyacentes
        $text = $this->removeAdjacentSeparators($text);

        // 5. Aplicar trim final
        return trim($text);
    }

    /**
     * Elimina separadores huérfanos al inicio o final del texto
     */
    private function removeOrphanSeparators(string $text): string
    {
        $text = trim($text);

        foreach (self::ORPHAN_SEPARATORS as $separator) {
            $escaped = preg_quote($separator, '/');

            // Eliminar al inicio (con espacios opcionales)
            $text = preg_replace('/^\s*'.$escaped.'\s+/', '', $text);

            // Eliminar al final (con espacios opcionales)
            $text = preg_replace('/\s+'.$escaped.'\s*$/', '', $text);
        }

        return trim($text);
    }

    /**
     * Elimina separadores duplicados o adyacentes
     * Ejemplo: "text - - text" -> "text - text"
     * Ejemplo: "text -" (al final) -> "text"
     */
    private function removeAdjacentSeparators(string $text): string
    {
        foreach (self::ORPHAN_SEPARATORS as $separator) {
            $escaped = preg_quote($separator, '/');

            // Eliminar separadores duplicados: "- -" -> "-"
            $text = preg_replace(
                '/('.$escaped.'\s*)+/',
                $separator.' ',
                $text
            );
        }

        return $text;
    }

    /**
     * Valida si un nombre de variable es seguro
     */
    public function isValidVariableName(string $name): bool
    {
        return preg_match('/^[a-zA-Z0-9_.\-]+$/', $name) === 1;
    }

    /**
     * Extrae todos los nombres de variables de un texto
     *
     * @return array<string>
     */
    public function extractVariableNames(string $text): array
    {
        preg_match_all(self::VARIABLE_PATTERN, $text, $matches);

        return $matches[1] ?? [];
    }
}
