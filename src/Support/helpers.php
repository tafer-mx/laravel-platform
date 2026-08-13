<?php

use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Storyblok\StoryblokLinkResolver;

// TODO: These helpers were moved as-is from the consumer projects and should be refactored into a more optimal design.

if (! function_exists('storyblokAssetExtension')) {
    /**
     * Get lowercase extension from Storyblok asset URL path.
     */
    function storyblokAssetExtension(string $assetUrl): string
    {
        $path = parse_url($assetUrl, PHP_URL_PATH) ?: '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return is_string($extension) ? strtolower($extension) : '';
    }
}

if (! function_exists('storyblokCanForceWebp')) {
    /**
     * Decide whether the asset can be transformed to WebP.
     * Excludes SVG, video files and non-image document/archive assets.
     */
    function storyblokCanForceWebp(string $assetUrl): bool
    {
        if ($assetUrl === '' || ! str_contains($assetUrl, 'storyblok.com')) {
            return false;
        }

        $extension = storyblokAssetExtension($assetUrl);
        if ($extension === '') {
            return true;
        }

        $excludedExtensions = [
            // Vector
            'svg',
            // Video
            'mp4', 'webm', 'mov', 'ogg', 'avi', 'mkv', 'wmv', 'm4v',
            // Files / docs / archives
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'rar', '7z', 'txt', 'csv',
        ];

        return ! in_array($extension, $excludedExtensions, true);
    }
}

if (! function_exists('storyblokImage')) {
    /**
     * Optimiza imágenes usando la API nativa de Storyblok Image Service
     *
     * @param  string  $imageUrl  URL base de la imagen de Storyblok
     * @param  array  $options  Opciones de optimización
     * @return string URL optimizada
     */
    function storyblokImage($imageUrl, $options = [])
    {
        if (empty($imageUrl) || ! str_contains($imageUrl, 'storyblok.com')) {
            return $imageUrl;
        }

        // Keep original URL for assets we should not convert (svg/video/files).
        if (! storyblokCanForceWebp((string) $imageUrl)) {
            return $imageUrl;
        }

        // Configuración por defecto
        $defaults = [
            'width' => null,
            'height' => null,
            'quality' => 80,
            'format' => 'webp',
            'fit' => 'cover', // cover, contain, fill, inside, outside
            'filters' => [],
        ];

        $config = array_merge($defaults, $options);
        // Business rule: always use WebP for transformable assets.
        $config['format'] = 'webp';

        // Construir la URL base con /m/ para habilitar el servicio de imágenes
        $optimizedUrl = rtrim($imageUrl, '/').'/m/';

        // Agregar dimensiones si se especifican
        if ($config['width'] || $config['height']) {
            $width = $config['width'] ?: '';
            $height = $config['height'] ?: '';
            $optimizedUrl .= $width.'x'.$height.'/';
        }

        // Construir filtros
        $filters = [];

        // Calidad
        if ($config['quality'] !== 80) {
            $filters[] = "quality({$config['quality']})";
        }

        // Formato (siempre webp para assets transformables)
        $filters[] = 'format(webp)';

        // Fit mode
        if ($config['fit'] !== 'cover') {
            $filters[] = "fit({$config['fit']})";
        }

        // Filtros adicionales
        if (! empty($config['filters'])) {
            $filters = array_merge($filters, $config['filters']);
        }

        // Agregar filtros a la URL
        if (! empty($filters)) {
            $optimizedUrl .= 'filters:'.implode(':', $filters);
        }

        return $optimizedUrl;
    }
}

if (! function_exists('storyblokImageResponsive')) {
    /**
     * Genera URLs responsivas para diferentes breakpoints
     *
     * @param  string  $imageUrl  URL base de la imagen
     * @param  array  $breakpoints  Configuración de breakpoints
     * @return array URLs optimizadas por breakpoint
     */
    function storyblokImageResponsive($imageUrl, $breakpoints = [])
    {
        $defaultBreakpoints = [
            'mobile' => ['width' => 480, 'quality' => 75],
            'tablet' => ['width' => 768, 'quality' => 80],
            'desktop' => ['width' => 1200, 'quality' => 85],
            'large' => ['width' => 1920, 'quality' => 90],
        ];

        $breakpoints = array_merge($defaultBreakpoints, $breakpoints);
        $responsiveImages = [];

        foreach ($breakpoints as $name => $config) {
            $responsiveImages[$name] = storyblokImage($imageUrl, $config);
        }

        return $responsiveImages;
    }
}

if (! function_exists('storyblokImageSrcset')) {
    /**
     * Genera un srcset para imágenes responsivas
     *
     * @param  string  $imageUrl  URL base de la imagen
     * @param  array  $sizes  Tamaños para el srcset
     * @return string Srcset formateado
     */
    function storyblokImageSrcset($imageUrl, $sizes = [])
    {
        $defaultSizes = [480, 768, 1024, 1200, 1920];
        $sizes = $sizes ?: $defaultSizes;

        $srcsetParts = [];

        foreach ($sizes as $width) {
            $optimizedUrl = storyblokImage($imageUrl, [
                'width' => $width,
                'quality' => $width <= 768 ? 75 : 80,
            ]);
            $srcsetParts[] = "{$optimizedUrl} {$width}w";
        }

        return implode(', ', $srcsetParts);
    }
}

const SIZE_IMAGES = [
    'small' => '400x0',
    'medium' => '900x0',
    'large' => '1440x0',
];

if (! function_exists('cleanStoryblokText')) {
    /**
     * Clean problematic characters from Storyblok text content
     *
     * @param  string  $text  The text to clean
     * @return string The cleaned text
     */
    function cleanStoryblokText($text)
    {
        if (! is_string($text)) {
            return '';
        }

        // Remove replacement characters and other problematic Unicode
        $text = str_replace([
            '�', // Replacement character
            "\xEF\xBF\xBD", // UTF-8 replacement character
            "\xC2\xA0", // Non-breaking space
            "\xE2\x80\x8B", // Zero-width space
            "\xE2\x80\x8C", // Zero-width non-joiner
            "\xE2\x80\x8D", // Zero-width joiner
            "\xE2\x80\x8E", // Left-to-right mark
            "\xE2\x80\x8F", // Right-to-left mark
            "\xE2\x80\xA8", // Line separator
            "\xE2\x80\xA9", // Paragraph separator
        ], '', $text);

        // Normalize quotes and dashes
        $text = str_replace(["\u201C", "\u201D", "\u2018", "\u2019"], ['"', '"', "'", "'"], $text);
        $text = str_replace(['–', '—'], '-', $text);

        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}

/**
 * Obtiene y procesa un SVG de Storyblok para permitir control de colores
 *
 * @param  string  $url  URL del SVG en Storyblok
 * @param  array  $classes  Clases CSS para aplicar al SVG
 * @param  string  $alt  Texto alternativo para accesibilidad
 * @param  string  $color  Color CSS para aplicar al SVG (hex, var(), etc.)
 * @return string Contenido SVG procesado o URL original si falla
 */
if (! function_exists('getSvgContent')) {
    function getSvgContent($url, $classes = [], $alt = '', $color = null)
    {
        // Si no es una URL de Storyblok, devolver la original
        if (! str_contains($url, 'a.storyblok.com')) {
            return $url;
        }

        try {
            // Obtener el contenido del SVG
            $svgContent = file_get_contents($url);

            // Optimizar el SVG eliminando atributos de color inline
            $svgContent = preg_replace('/\s(fill|stroke)="[^"]*"/i', '', $svgContent);

            // Añadir fill="currentColor" para herencia de color
            $svgContent = preg_replace('/<path([^>]*)>/i', '<path$1 fill="currentColor">', $svgContent);
            $svgContent = preg_replace('/<circle([^>]*)>/i', '<circle$1 fill="currentColor">', $svgContent);
            $svgContent = preg_replace('/<rect([^>]*)>/i', '<rect$1 fill="currentColor">', $svgContent);

            // Preparar atributos para el SVG
            $attributes = [];

            // Añadir color personalizado si se especifica
            if ($color) {
                $attributes[] = 'style="color: '.$color.';"';
            }

            // Añadir clases CSS si se especifican
            if (! empty($classes)) {
                $attributes[] = 'class="'.implode(' ', $classes).'"';
            }

            // Aplicar todos los atributos al SVG
            if (! empty($attributes)) {
                $attributeString = implode(' ', $attributes);
                $svgContent = preg_replace('/<svg([^>]*)>/i', '<svg$1 '.$attributeString.'>', $svgContent);
            }

            return $svgContent;
        } catch (Exception $e) {
            // En caso de error, devolver la URL original
            return $url;
        }
    }
}

if (! function_exists('customFilterImage')) {
    function customFilterImage($urlImage = '', $size = 'large', $filter = '', $format = 'webp')
    {
        // Si no es una URL de Storyblok, devolver la original
        if (! str_contains($urlImage, 'a.storyblok.com')) {
            return $urlImage;
        }

        // Añadir tamaño responsivo
        $transformSize = str_replace('px', '', $size);
        $customSize = SIZE_IMAGES[$size] ?? '0x'.$transformSize;
        $urlImage = $urlImage.'/m/'.$customSize;

        // Añadir el filtro a la URL
        return $urlImage.'/filters:format('.$format.')'.$filter;
    }
}

if (! function_exists('generateResponsiveSrcset')) {
    /**
     * Genera un srcset responsivo para múltiples tamaños de imagen
     *
     * @param  string  $urlImage  URL de la imagen base
     * @param  array  $sizes  Array de tamaños a generar ['small', 'medium', 'large']
     * @param  string  $filter  Filtros adicionales a aplicar
     * @param  string  $format  Formato de imagen (webp, jpg, png)
     * @return string Srcset formatted string
     */
    function generateResponsiveSrcset($urlImage = '', $sizes = ['small', 'medium', 'large'], $filter = '', $format = 'webp')
    {
        if (empty($urlImage) || ! str_contains($urlImage, 'a.storyblok.com')) {
            return '';
        }

        $srcset = [];
        foreach ($sizes as $size) {
            $optimizedUrl = customFilterImage($urlImage, $size, $filter, $format);
            $sizeConfig = SIZE_IMAGES[$size] ?? null;

            if ($sizeConfig) {
                $width = explode('x', $sizeConfig)[0];
                if ($width && $width !== '0') {
                    $srcset[] = "$optimizedUrl {$width}w";
                }
            }
        }

        return implode(', ', $srcset);
    }
}

if (! function_exists('getImageDimensions')) {
    /**
     * Obtiene las dimensiones configuradas para un tamaño específico
     *
     * @param  string  $size  Tamaño de imagen (small, medium, large)
     * @return array Array con width y height
     */
    function getImageDimensions($size = 'large')
    {
        $sizeConfig = SIZE_IMAGES[$size] ?? SIZE_IMAGES['large'];
        $dimensions = explode('x', $sizeConfig);

        return [
            'width' => (int) ($dimensions[0] ?? 1440),
            'height' => (int) ($dimensions[1] ?? 0),
        ];
    }
}

if (! function_exists('resolve_link')) {
    /**
     * Resolve a Storyblok link array to a public URL.
     */
    function resolve_link(?array $link, ?string $lang = null): string
    {
        if ($lang === null) {
            try {
                $ctx = app(RequestCtx::class);
                $lang = $ctx->locale->value ?? 'en';
            } catch (Throwable) {
                $lang = app()->getLocale() ?: 'en';
            }
        }

        return StoryblokLinkResolver::resolve($link, $lang);
    }
}
