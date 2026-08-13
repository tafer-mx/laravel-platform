<?php

namespace TAFER\Core\Storyblok\Tiptap\Marks;

use Storyblok\Tiptap\Mark\Link;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class CustomLink extends Link
{
    public function addOptions()
    {
        return [
            'HTMLAttributes' => [
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ],
        ];
    }

    public function addAttributes()
    {
        return [
            'href' => [
                'default' => null,
                'renderHTML' => function ($attributes) {
                    $href = is_object($attributes) ? ($attributes->href ?? null) : ($attributes['href'] ?? null);

                    if (! $href) {
                        return null;
                    }

                    if (! empty($href) && str_starts_with($href, 'http://')) {
                        $href = str_replace('http://', 'https://', $href);
                    }

                    return [
                        'href' => $href,
                    ];
                },
            ],
            'target' => [
                'default' => $this->options['HTMLAttributes']['target'] ?? null,
            ],
            'rel' => [
                'default' => $this->options['HTMLAttributes']['rel'] ?? null,
                'renderHTML' => function ($attributes) {
                    $linkType = is_object($attributes) ? ($attributes->linktype ?? null) : ($attributes['linktype'] ?? null);
                    $href = is_object($attributes) ? ($attributes->href ?? null) : ($attributes['href'] ?? null);

                    if ($linkType !== 'url' || ! is_string($href) || self::isInternalUrl($href)) {
                        return null;
                    }

                    return ['rel' => 'noopener noreferrer nofollow'];
                },
            ],
        ];
    }

    private static function isInternalUrl(string $href): bool
    {
        $host = parse_url($href, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return true;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($appHost) || $appHost === '') {
            return false;
        }

        return self::normalizeHost($host) === self::normalizeHost($appHost);
    }

    private static function normalizeHost(string $host): string
    {
        return preg_replace('/^www\./', '', strtolower($host));
    }
}
