<?php

namespace TAFER\Core\Storyblok;

use TAFER\Core\Context\RequestCtx;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
trait LoadsGlobalConfig
{
    public function getGlobalConfig(RequestCtx $ctx, bool $isPreview = false): ?array
    {
        $locationScoped = (bool) config(
            'tafer.storyblok.global_config.location_scoped',
            true,
        );

        $slug = $this->globalConfigSlug($ctx, $locationScoped);

        return $this->getStory($slug, $isPreview, $ctx->locale->value);
    }

    private function globalConfigSlug(RequestCtx $ctx, bool $locationScoped): string
    {
        $brandRoot = "brands/{$ctx->resort->value}";

        if (! $locationScoped || $ctx->location->isCorp()) {
            return "{$brandRoot}/config_brand";
        }

        $locationRoot = "{$brandRoot}/{$ctx->location->value}";
        $childResort = isset($ctx->childResort) ? $ctx->childResort : null;
        $hasValidChildResort = $childResort !== null
            && $childResort->parent() === $ctx->resort
            && $childResort->hasRegion($ctx->location);

        if ($hasValidChildResort) {
            return "{$locationRoot}/config_brand_{$ctx->location->value}-{$childResort->value}";
        }

        return "{$locationRoot}/config_brand_{$ctx->location->value}";
    }
}
