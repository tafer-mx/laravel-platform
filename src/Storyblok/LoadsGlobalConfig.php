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

        $slug = $locationScoped && ! $ctx->location->isCorp()
            ? "brands/{$ctx->resort->value}/{$ctx->location->value}/config_brand_{$ctx->location->value}"
            : "brands/{$ctx->resort->value}/config_brand";

        return $this->getStory($slug, $isPreview, $ctx->locale->value);
    }
}
