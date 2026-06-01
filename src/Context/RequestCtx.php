<?php 

namespace TAFER\Core\Context;

use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Records\ResortRegion;

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
    public Resort $resort;
    public Locale $locale;
    public Location $location;
    public string $slug;
    public bool $isPreview;
    //TODO: ADD DEVICE public string $device;

    public function __construct(string $brandSlug)
    {
        $this->resort = Resort::tryFrom($brandSlug) ?? throw new \InvalidArgumentException("Invalid resort slug: {$brandSlug}");
        $this->isPreview = false; //default value, can be changed later with the setter
    }

    /**
     * Set the resort for this request context.
     *
     * @param Resort $resort
     * @return self
     */
    public function setResort(Resort $resort): self
    {
        $this->resort = $resort;
        return $this;
    }

    /**
     * Set the locale for this request context.
     *
     * @param Locale $locale
     * @return self
     */
    public function setLocale(Locale $locale): self
    {
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
        $this->isPreview = $isPreview;
        return $this;
    }
}