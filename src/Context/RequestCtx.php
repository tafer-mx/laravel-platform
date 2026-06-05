<?php 

namespace TAFER\Core\Context;

use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;

/**
 * Class RequestCtx
 * 
 * Encapsulates the contextual information of each request.
 * This class centralizes commond data such as slug, locale, resort, etc
 * 
 * It is intended to be resolved globally via the service container, 
 * so it can be injected in any class that needs it.
 * 
 * @package TAFER\Core\Context
 */

class RequestCtx
{
    public readonly Resort $resort;
    public readonly Locale $locale;
    public readonly Location $location;
    public readonly string $slug;
    public readonly bool $isPreview;
    //TODO: ADD DEVICE public string $device;

    public function __construct(string $brandSlug)
    {
        $this->resort = Resort::tryFrom($brandSlug) ?? throw new \InvalidArgumentException("Invalid resort slug: {$brandSlug}");
    }

    /**
     * Set the locale for this request context.
     *
     * @param Locale $locale
     * @return self
     */
    public function setLocale(Locale $locale): self
    {
        $this->ensurePropertyCanBeSet('locale');
        $this->locale = $locale;
        return $this;
    }

    /**
     * Set the location for this request context.
     *
     * @param Location $location
     * @return self
     */
    public function setLocation(Location $location): self
    {
        $this->ensurePropertyCanBeSet('location');
        $this->location = $location;
        return $this;
    }

    /**
     * Set the slug for this request context.
     *
     * @param string $slug
     * @return self
     */
    public function setSlug(string $slug): self
    {
        $this->ensurePropertyCanBeSet('slug');
        $this->slug = $slug;
        return $this;
    }

    /**
     * Set whether this request is in preview mode.
     *
     * @param bool $isPreview
     * @return self
     */
    public function setIsPreview(bool $isPreview): self
    {
        $this->ensurePropertyCanBeSet('isPreview');
        $this->isPreview = $isPreview;
        return $this;
    }

    private function ensurePropertyCanBeSet(string $property): void
    {
        if ((new \ReflectionProperty($this, $property))->isInitialized($this)) {
            throw new \LogicException("RequestCtx property [{$property}] has already been set.");
        }
    }
}
