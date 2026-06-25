<?php

namespace TAFER\Core\Context;

use InvalidArgumentException;
use LogicException;
use ReflectionProperty;
use Storyblok\Api\Domain\Value\Dto\Version;
use Storyblok\Api\Request\StoryRequest;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Storyblok\StoryblokRequestFactory;

/**
 * Request-scoped information shared by controllers, views, and services.
 */
class RequestCtx
{
    public readonly Resort $resort;

    public readonly Locale $locale;

    public readonly Location $location;

    public readonly string $slug;

    public readonly bool $isPreview;

    public readonly Device $device;

    public function __construct(string $brandSlug)
    {
        $this->resort = Resort::tryFrom($brandSlug)
            ?? throw new InvalidArgumentException("Invalid resort slug: {$brandSlug}");
    }

    public function setLocale(Locale $locale): self
    {
        $this->setOnce('locale', $locale);

        return $this;
    }

    public function setLocation(Location $location): self
    {
        $this->setOnce('location', $location);

        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->setOnce('slug', trim($slug, '/') ?: '/');

        return $this;
    }

    public function setIsPreview(bool $isPreview): self
    {
        $this->setOnce('isPreview', $isPreview);

        return $this;
    }

    public function setDevice(Device $device): self
    {
        $this->setOnce('device', $device);

        return $this;
    }

    /**
     * Build the canonical Storyblok path for the current request.
     *
     * Corporate/root sites omit the synthetic "corp" segment. Root pages use
     * Storyblok's home-* prefix convention.
     */
    public function storyblokSlug(string $root = 'brands'): string
    {
        $location = $this->location->isCorp() ? '' : $this->location->value;
        $slug = $this->slug === '/' ? '' : $this->slug;

        if ($location !== '' && ($slug === $location || str_starts_with($slug, "{$location}/"))) {
            $slug = ltrim(substr($slug, strlen($location)), '/');
        }

        if ($slug === '') {
            $slug = $location === ''
                ? "home-{$this->resort->value}"
                : "home-{$location}";
        }

        $segments = [$root, $this->resort->value, $location, $slug];

        return implode('/', array_values(array_filter(
            array_map(static fn (string $segment): string => trim($segment, '/'), $segments),
            static fn (string $segment): bool => $segment !== '',
        )));
    }

    public function storyblokRequest(StoryblokRequestFactory $factory): StoryRequest
    {
        return $factory->make(
            new StoryRequest(
                language: $this->locale->value,
                version: $this->isPreview ? Version::Draft : Version::Published,
            ),
            Version::Published,
        );
    }

    private function setOnce(string $property, mixed $value): void
    {
        if ((new ReflectionProperty($this, $property))->isInitialized($this)) {
            throw new LogicException("RequestCtx property [{$property}] has already been set.");
        }

        $this->{$property} = $value;
    }
}
