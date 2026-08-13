<?php

namespace TAFER\Core\Support;

use Carbon\Carbon;

// TODO: This implementation was moved as-is from the consumer projects and should be refactored into a more optimal design.
class ConditionalOfferHelper
{
    /**
     * Mapeo de días de Storyblok a números de Carbon (1=Lunes, 7=Domingo)
     */
    private const DAY_MAP = [
        'Monday' => Carbon::MONDAY,
        'Tuesday' => Carbon::TUESDAY,
        'Wednesday' => Carbon::WEDNESDAY,
        'Thursday' => Carbon::THURSDAY,
        'Friday' => Carbon::FRIDAY,
        'Saturday' => Carbon::SATURDAY,
        'Sunday' => Carbon::SUNDAY,
    ];

    /**
     * Verifica si una oferta condicional está activa en este momento
     *
     * @param  array  $daysOfWeek  Días seleccionados (ej: ['Monday', 'Tuesday'])
     * @param  array  $timeSlots  Intervalos de tiempo (ej: [['Start_Time' => '11:00', 'End_Time' => '12:00']])
     * @param  string  $timezone  Zona horaria (ej: 'America/Mexico_City')
     * @return array ['isActive' => bool, 'nextEnd' => Carbon|null]
     */
    public static function isOfferActive(array $daysOfWeek, array $timeSlots, string $timezone = 'America/Mexico_City'): array
    {
        $now = Carbon::now($timezone);

        // Convertir días seleccionados a números
        $activeDays = collect($daysOfWeek)
            ->map(fn ($day) => self::DAY_MAP[$day] ?? null)
            ->filter()
            ->values()
            ->toArray();

        if (empty($activeDays)) {
            return ['isActive' => false, 'nextEnd' => null];
        }

        $isActiveDay = in_array($now->dayOfWeekIso, $activeDays);
        $currentTime = $now->format('H:i');

        // Sin time slots = activo 24 horas
        if (empty($timeSlots)) {
            if ($isActiveDay) {
                $endOfDay = $now->copy()->endOfDay();

                return ['isActive' => true, 'nextEnd' => $endOfDay];
            }

            return ['isActive' => false, 'nextEnd' => null];
        }

        // Verificar cada time slot
        $yesterdayNumber = $now->copy()->subDay()->dayOfWeekIso;
        $wasActiveDayYesterday = in_array($yesterdayNumber, $activeDays);

        foreach ($timeSlots as $slot) {
            $startTime = $slot['Start_Time'] ?? null;
            $endTime = $slot['End_Time'] ?? null;

            if (! $startTime || ! $endTime) {
                continue;
            }

            $crossesMidnight = $startTime > $endTime;

            if ($crossesMidnight) {
                // Intervalo cruza medianoche (ej: 17:00 a 08:00)
                if ($isActiveDay && $currentTime >= $startTime) {
                    $nextEnd = self::createTimeCarbon($now->copy()->addDay(), $endTime, $timezone);

                    return ['isActive' => true, 'nextEnd' => $nextEnd];
                }

                if ($wasActiveDayYesterday && $currentTime < $endTime) {
                    $nextEnd = self::createTimeCarbon($now, $endTime, $timezone);

                    return ['isActive' => true, 'nextEnd' => $nextEnd];
                }
            } else {
                // Intervalo normal (ej: 11:00 a 12:00)
                if ($isActiveDay && $currentTime >= $startTime && $currentTime < $endTime) {
                    $nextEnd = self::createTimeCarbon($now, $endTime, $timezone);

                    return ['isActive' => true, 'nextEnd' => $nextEnd];
                }
            }
        }

        // Calcular próximo inicio para slides inactivos
        $nextStart = self::getNextStart($now, $activeDays, $timeSlots, $timezone);

        return ['isActive' => false, 'nextEnd' => null, 'nextStart' => $nextStart];
    }

    /**
     * Calcula el próximo inicio del slide condicional
     */
    private static function getNextStart(Carbon $now, array $activeDays, array $timeSlots, string $timezone): ?Carbon
    {
        $currentTime = $now->format('H:i');
        $isActiveDay = in_array($now->dayOfWeekIso, $activeDays);

        // Sin time slots = el próximo día activo a las 00:00
        if (empty($timeSlots)) {
            return self::getNextActiveDay($now, $activeDays, $timezone);
        }

        // Ordenar time slots por hora de inicio
        $sortedSlots = collect($timeSlots)
            ->filter(fn ($slot) => isset($slot['Start_Time']) && isset($slot['End_Time']))
            ->sortBy('Start_Time')
            ->values()
            ->toArray();

        // Si es día activo, buscar el próximo slot hoy
        if ($isActiveDay) {
            foreach ($sortedSlots as $slot) {
                $startTime = $slot['Start_Time'];
                if ($currentTime < $startTime) {
                    return self::createTimeCarbon($now, $startTime, $timezone);
                }
            }
        }

        // Buscar el próximo día activo
        $nextActiveDay = self::getNextActiveDay($now, $activeDays, $timezone);
        if ($nextActiveDay && ! empty($sortedSlots)) {
            $firstSlotStart = $sortedSlots[0]['Start_Time'];

            return self::createTimeCarbon($nextActiveDay, $firstSlotStart, $timezone);
        }

        return $nextActiveDay;
    }

    /**
     * Obtiene el próximo día activo
     */
    private static function getNextActiveDay(Carbon $now, array $activeDays, string $timezone): ?Carbon
    {
        for ($i = 1; $i <= 7; $i++) {
            $checkDate = $now->copy()->addDays($i);
            if (in_array($checkDate->dayOfWeekIso, $activeDays)) {
                return $checkDate->startOfDay();
            }
        }

        return null;
    }

    /**
     * Crea un Carbon con la hora especificada
     */
    private static function createTimeCarbon(Carbon $date, string $time, string $timezone): Carbon
    {
        [$hour, $minute] = explode(':', $time);

        return $date->copy()->setTimezone($timezone)->setTime((int) $hour, (int) $minute, 0);
    }

    /**
     * Formatea un Carbon a ISO 8601 para JavaScript
     */
    public static function formatForJs(?Carbon $carbon): ?string
    {
        return $carbon?->toIso8601String();
    }
}
