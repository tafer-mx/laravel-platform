<?php

namespace TAFER\Core\Support;

use TAFER\Core\Context\StoryblokBlockContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Throwable;

final class OfferValidityHelper
{
    private const STATUS_ACTIVE = 'active';
    private const STATUS_OPAQUE = 'opaque';
    private const STATUS_INACTIVE = 'inactive';

    private const ACTIVATION_WEEKLY = 'weekly';
    private const ACTIVATION_DATE_RANGES = 'date_ranges';

    /**
     * Evalúa completamente la visibilidad del slide.
     *
     * @return array{
     *     shouldRender: bool,
     *     isOpaque: bool,
     *     isConditional: bool,
     *     status: string,
     *     activation: string,
     *     nextEnd: ?Carbon,
     *     reason: string
     * }
     */
    public static function evaluate(
        StoryblokBlockContext $context,
        ?string $timezone = null,
        ?CarbonInterface $now = null,
    ): array {
        $timezone ??= resort_timezone()
            ?? 'America/Mexico_City';

        $currentDate = $now
            ? Carbon::instance($now)->setTimezone($timezone)
            : Carbon::now($timezone);

        $status = $context->get('status');

        $activation = $context->get('activation');

        /*
         * inactive nunca debe renderizarse.
         */
        if ($status === self::STATUS_INACTIVE || $status === self::STATUS_OPAQUE ) {
            return self::result(
                shouldRender: false,
                isOpaque: false,
                isConditional: false,
                status: $status,
                activation: $activation,
                reason: 'status_inactive',
            );
        }

        /*
         * Activación semanal.
         */
        if ($activation === self::ACTIVATION_WEEKLY) {
            $weeklyResult = self::evaluateWeekly(
                recurringDays: $context->get(
                    'date_recurring_days',
                    []
                ),
                now: $currentDate,
            );

            return self::result(
                shouldRender: $weeklyResult['isActive'],
                isOpaque:
                    $weeklyResult['isActive']
                    && $status === self::STATUS_OPAQUE,
                isConditional: true,
                status: $status,
                activation: $activation,
                nextEnd: $weeklyResult['nextEnd'],
                reason: $weeklyResult['reason'],
            );
        }

        /*
         * Activación mediante uno o varios rangos.
         */
        if ($activation === self::ACTIVATION_DATE_RANGES) {
            $rangesResult = self::evaluateDateRanges(
                ranges: $context->get(
                    'date_time_range',
                    []
                ),
                now: $currentDate,
                timezone: $timezone,
            );

            return self::result(
                shouldRender: $rangesResult['isActive'],
                isOpaque:
                    $rangesResult['isActive']
                    && $status === self::STATUS_OPAQUE,
                isConditional: true,
                status: $status,
                activation: $activation,
                nextEnd: $rangesResult['nextEnd'],
                reason: $rangesResult['reason'],
            );
        }

        /*
         * Activation inválido o ausente.
         * Se conserva el slide para evitar ocultarlo por error.
         */
        return self::result(
            shouldRender: true,
            isOpaque: $status === self::STATUS_OPAQUE,
            isConditional: false,
            status: $status,
            activation: $activation,
            reason: 'activation_not_configured',
        );
    }

    /**
     * Devuelve únicamente si el slide debe renderizarse.
     */
    public static function shouldRender(
        StoryblokBlockContext $context,
        ?string $timezone = null,
        ?CarbonInterface $now = null,
    ): bool {
        return self::evaluate(
            context: $context,
            timezone: $timezone,
            now: $now,
        )['shouldRender'];
    }

    /**
     * Activo durante todos los días seleccionados.
     *
     * @return array{
     *     isActive: bool,
     *     nextEnd: ?Carbon,
     *     reason: string
     * }
     */
    public static function evaluateWeekly(
        mixed $recurringDays,
        CarbonInterface $now,
    ): array {
        if (! is_array($recurringDays)) {
            return [
                'isActive' => false,
                'nextEnd' => null,
                'reason' => 'invalid_recurring_days',
            ];
        }

        if ($recurringDays === []) {
            return [
                'isActive' => false,
                'nextEnd' => null,
                'reason' => 'empty_recurring_days',
            ];
        }

        $currentDay = strtolower($now->englishDayOfWeek);

        $isActive = in_array(
            $currentDay,
            $recurringDays,
            true
        );

        return [
            'isActive' => $isActive,
            'nextEnd' => $isActive
                ? Carbon::instance($now)->endOfDay()
                : null,
            'reason' => $isActive
                ? 'weekly_active'
                : 'weekly_inactive',
        ];
    }

    /**
     * Activo cuando la fecha actual está dentro
     * de cualquiera de los rangos.
     *
     * @return array{
     *     isActive: bool,
     *     nextEnd: ?Carbon,
     *     reason: string
     * }
     */
    public static function evaluateDateRanges(
        mixed $ranges,
        CarbonInterface $now,
        string $timezone,
    ): array {
        if (! is_array($ranges) || $ranges === []) {
            return [
                'isActive' => false,
                'nextEnd' => null,
                'reason' => 'empty_date_ranges',
            ];
        }

        $activeRanges = [];

        foreach ($ranges as $range) {
            if (! is_array($range)) {
                continue;
            }

            $startDate = self::parseDate(
                $range['start_date'] ?? null,
                $timezone,
            );

            $endDate = self::parseDate(
                $range['end_date'] ?? null,
                $timezone,
            );

            if (! $startDate || ! $endDate) {
                continue;
            }

            /*
             * Ignorar rangos inválidos donde el final
             * ocurre antes del inicio.
             */
            if ($endDate->lt($startDate)) {
                continue;
            }

            /*
             * El inicio y el final están incluidos.
             */
            $isInsideRange =
                $now->greaterThanOrEqualTo($startDate)
                && $now->lessThanOrEqualTo($endDate);

            if ($isInsideRange) {
                $activeRanges[] = [
                    'start' => $startDate,
                    'end' => $endDate,
                ];
            }
        }

        if ($activeRanges === []) {
            return [
                'isActive' => false,
                'nextEnd' => null,
                'reason' => 'outside_date_ranges',
            ];
        }

        /*
         * Si dos rangos están activos al mismo tiempo,
         * utilizar el que finalice primero.
         */
        usort(
            $activeRanges,
            static fn (array $first, array $second): int =>
                $first['end']->timestamp
                <=> $second['end']->timestamp
        );

        return [
            'isActive' => true,
            'nextEnd' => $activeRanges[0]['end'],
            'reason' => 'date_range_active',
        ];
    }

    private static function parseDate(
        mixed $value,
        string $timezone,
    ): Carbon {
        return Carbon::createFromFormat(
            'Y-m-d H:i',
            $value,
            $timezone,
        );
    }


    private static function result(
        bool $shouldRender,
        bool $isOpaque,
        bool $isConditional,
        string $status,
        string $activation,
        ?Carbon $nextEnd = null,
        string $reason = '',
    ): array {
        return [
            'shouldRender' => $shouldRender,
            'isOpaque' => $isOpaque,
            'isConditional' => $isConditional,
            'status' => $status,
            'activation' => $activation,
            'nextEnd' => $nextEnd,
            'reason' => $reason,
        ];
    }
}