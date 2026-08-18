<?php

namespace TAFER\Core\Support;

use Carbon\Carbon;
use TAFER\Core\Context\StoryblokBlockContext;
use TAFER\Core\Services\StoryblokContextResolver;
use TAFER\Core\Support\ConditionalOfferHelper;
use TAFER\Core\Support\OfferValidityHelper;

class MenuItemHelper
{
    public static function processMenuItem(
        array $item,
        StoryblokBlockContext $parentContext,
        StoryblokContextResolver $contextResolver,
        object $requestCtx,
        string $timezone,
        Carbon $now
    ): array {
        $itemContext = $contextResolver->resolveFromBlok(
            $item,
            $parentContext,
            $requestCtx->isPreview,
            $requestCtx->locale->value,
        );

        $hasOwnContext = $itemContext !== $parentContext;

        if ($hasOwnContext) {
            $contextResult = OfferValidityHelper::evaluate(
                context: $itemContext,
                timezone: $timezone,
                now: $now,
            );

            if (!($contextResult['shouldRender'] ?? false)) {
                return [
                    'shouldShow' => false,
                    'hasContext' => true,
                    'context' => $itemContext,
                ];
            }

            return [
                'shouldShow' => true,
                'hasContext' => true,
                'context' => $itemContext,
                'isConditional' => $contextResult['isConditional'] ?? false,
                'isOpaque' => $contextResult['isOpaque'] ?? false,
                'status' => $contextResult['status'] ?? null,
                'activation' => $contextResult['activation'] ?? null,
                'endDate' => $contextResult['nextEnd'] ?? null,
            ];
        }

        return self::processLegacyConditional(
            $item,
            $timezone,
            $now
        );
    }

    private static function processLegacyConditional(
        array $item,
        string $timezone,
        Carbon $now
    ): array {
        $activeRaw = $item['Active'] ?? null;
        $conditionalRaw = $item['Conditional'] ?? null;

        $isActive = filter_var(
            $activeRaw,
            FILTER_VALIDATE_BOOLEAN
        );

        $isConditional = $conditionalRaw === null
            ? null
            : filter_var(
                $conditionalRaw,
                FILTER_VALIDATE_BOOLEAN
            );

        if ($isActive === true) {
            return [
                'shouldShow' => true,
                'hasContext' => false,
            ];
        }

        if ($isConditional !== true) {
            return [
                'shouldShow' => false,
                'hasContext' => false,
            ];
        }

        $daysOfWeek = $item['Days_Of_Week'] ?? [];
        $timeSlots = $item['Time_Slots'] ?? [];

        $startDateRaw = $item['Start_Date_Time']
            ?? $item['Start_Date']
            ?? null;

        $endDateRaw = $item['End_Date_Time']
            ?? $item['End_Date']
            ?? null;

        if (!empty($daysOfWeek)) {
            $result = ConditionalOfferHelper::isOfferActive(
                $daysOfWeek,
                $timeSlots,
                $timezone
            );

            return [
                'shouldShow' => $result['isActive'] ?? false,
                'hasContext' => false,
            ];
        }

        if ($startDateRaw || $endDateRaw) {
            $startDate = self::parseDate(
                $startDateRaw,
                $timezone
            );

            $endDate = self::parseDate(
                $endDateRaw,
                $timezone
            );

            if ($startDate && $now->lt($startDate)) {
                return [
                    'shouldShow' => false,
                    'hasContext' => false,
                ];
            }

            if ($endDate && $now->gt($endDate)) {
                return [
                    'shouldShow' => false,
                    'hasContext' => false,
                ];
            }

            return [
                'shouldShow' => true,
                'hasContext' => false,
            ];
        }

        return [
            'shouldShow' => false,
            'hasContext' => false,
        ];
    }

    public static function processMenuLink(
        array $item,
        ?array $itemResult = null
    ): array {
        $text = $item['item_menu_text'] ?? '';

        $link = self::resolveLink(
            $item,
            $itemResult
        );

        $rawUrl = self::extractUrl($link);

        $target = self::extractTarget(
            $link,
            $item
        );

        return [
            'text' => $text,
            'url' => self::normalizeUrl($rawUrl),
            'openNewTab' => $target === '_blank',
            'hasValidUrl' => $rawUrl !== '',
        ];
    }

    private static function resolveLink(
        array $item,
        ?array $itemResult
    ): mixed {
        if (
            ($itemResult['hasContext'] ?? false)
            && !empty($itemResult['context'])
        ) {
            $contextLink = $itemResult['context']->get('link');

            if (!empty($contextLink)) {
                return $contextLink;
            }
        }

        return $item['item_menu_link'] ?? null;
    }

    private static function extractUrl(mixed $link): string
    {
        if (empty($link)) {
            return '';
        }

        if (is_array($link)) {
            return trim(
                $link['cached_url']
                ?? $link['url']
                ?? ''
            );
        }

        if (is_string($link)) {
            return trim($link);
        }

        return '';
    }

    private static function extractTarget(
        mixed $link,
        array $item
    ): string {
        if (is_array($link) && !empty($link['target'])) {
            return $link['target'];
        }

        return $item['item_menu_link']['target'] ?? '';
    }

    private static function normalizeUrl(?string $url): string
    {
        if (empty($url)) {
            return '#';
        }

        $url = trim($url);

        if (str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }

        if (str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (
            str_starts_with($url, '#')
            || str_starts_with($url, 'tel:')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, '/')
        ) {
            return $url;
        }

        return '/' . $url;
    }

    private static function parseDate(
        ?string $date,
        string $timezone
    ): ?Carbon {
        if (empty($date)) {
            return null;
        }

        return Carbon::parse(
            str_replace(' ', 'T', trim($date)),
            $timezone
        );
    }
}