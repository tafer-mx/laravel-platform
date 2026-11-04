<?php

namespace TAFER\Core\Phones;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Contracts\PhoneResolver;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Records\PhoneNumber;
use TAFER\Core\Records\PhoneSchedule;

/**
 * Resuelve el teléfono de reservaciones a partir del mapa de números del sitio.
 *
 * Formato del mapa (por defecto `config/tollfree.php`). El nivel de location solo
 * existe cuando el sitio es location scoped:
 *
 *   [
 *       '{location}' => [
 *           '{locale}' => [
 *               'desktop' => ['url' => 'tel:+18009316063', 'button_text' => '+1 800 931 6063'],
 *               'mobile' => ['url' => 'tel:+18449330612', 'button_text' => '+1 844 933 0612'],
 *               '{partnerId}' => ['url' => 'tel:...', 'button_text' => '...'],
 *           ],
 *           'schedules' => [
 *               'bookline' => [
 *                   'from' => '23:00',
 *                   'to' => '06:00',
 *                   'phones' => ['{locale}' => ['url' => 'tel:...', 'button_text' => '...']],
 *               ],
 *           ],
 *       ],
 *   ]
 *
 * Reglas:
 *  1. El partner siempre se registra, para que la cookie se conserve al cambiar de idioma.
 *  2. Un horario activo a la hora actual (zona horaria del sitio) gana sobre
 *     cualquier otro número, si define un número para el idioma.
 *  3. El número de partner solo aplica en los idiomas de partner (el idioma
 *     principal, salvo que el sitio indique otros) y solo si el mapa lo define.
 *  4. Si no, se usa el número del dispositivo. La tablet cuenta como mobile.
 *  5. Un idioma sin números usa los del idioma principal.
 */
final readonly class ConfigPhoneResolver implements PhoneResolver
{
    /**
     * Llave que agrupa los horarios dentro de un nivel del mapa de números.
     */
    public const SCHEDULES_KEY = 'schedules';

    /**
     * @param  array<string, mixed>  $phones  Mapa de números del sitio.
     * @param  PartnerTracker  $partners  Registro del partner del visitante.
     * @param  Locale  $defaultLocale  Idioma principal del sitio, usado como fallback.
     * @param  list<Locale>  $partnerLocales  Idiomas en los que aplican los números de partner.
     * @param  bool  $locationScoped  Indica si el mapa está agrupado por location.
     * @param  string  $timezone  Zona horaria del sitio, para horarios sin zona horaria propia.
     * @param  string|null  $timeOverrideParam  Parámetro del query string que fuerza la hora (HH:MM). Null lo desactiva.
     */
    public function __construct(
        private array $phones,
        private PartnerTracker $partners,
        private Locale $defaultLocale = Locale::English,
        private array $partnerLocales = [Locale::English],
        private bool $locationScoped = false,
        private string $timezone = 'UTC',
        private ?string $timeOverrideParam = null,
    ) {
        $this->schedules = $this->parseSchedules();
    }

    /**
     * Horarios válidos por nivel del mapa (valor de la location, o '' en sitios de un solo hotel).
     *
     * Se leen una vez por instancia (scoped = una vez por request) y no en cada resolve().
     *
     * @var array<string, list<PhoneSchedule>>
     */
    private array $schedules;

    public function resolve(
        Request $request,
        Locale $locale,
        Device $device,
        ?Location $location = null,
    ): ?PhoneNumber {
        $location = $this->scope($location);
        $partner = $this->partners->resolve($request, $location);
        [$locale, $phones] = $this->localePhones($locale, $location);

        if ($phones === []) {
            return null;
        }

        $scheduled = $this->scheduledPhone($request, $locale, $location);

        if ($scheduled !== null) {
            return $scheduled;
        }

        if ($partner !== null && in_array($locale, $this->partnerLocales, true)) {
            $phone = PhoneNumber::tryFromArray($phones[$partner] ?? null);

            if ($phone !== null) {
                return $phone;
            }
        }

        $device = $device->isMobile() ? Device::Mobile : Device::Desktop;

        return PhoneNumber::tryFromArray($phones[$device->value] ?? null);
    }

    public function forContext(Request $request, RequestCtx $context): ?PhoneNumber
    {
        return $this->resolve(
            request: $request,
            locale: $context->locale,
            device: $context->device,
            location: $context->location,
        );
    }

    public function basePhone(Locale $locale, ?Location $location = null): ?PhoneNumber
    {
        [, $phones] = $this->localePhones($locale, $this->scope($location));

        return PhoneNumber::tryFromArray($phones[Device::Desktop->value] ?? null);
    }

    /**
     * Obtiene el número del primer horario activo en este momento para el idioma.
     */
    private function scheduledPhone(Request $request, Locale $locale, ?Location $location): ?PhoneNumber
    {
        $schedules = $this->schedules[$location->value ?? ''] ?? [];

        if ($schedules === []) {
            return null;
        }

        $override = $this->timeOverride($request);

        foreach ($schedules as $schedule) {
            $phone = $schedule->phoneFor($locale);

            if ($phone !== null && $schedule->covers($override ?? $this->currentMinute($schedule))) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * Lee los horarios de cada nivel del mapa de números, conservando su orden.
     *
     * @return array<string, list<PhoneSchedule>>
     */
    private function parseSchedules(): array
    {
        $scopes = $this->locationScoped
            ? array_filter($this->phones, is_array(...))
            : ['' => $this->phones];

        $parsed = [];

        foreach ($scopes as $scope => $phones) {
            $schedules = $phones[self::SCHEDULES_KEY] ?? null;

            if (! is_array($schedules)) {
                continue;
            }

            $valid = [];

            foreach ($schedules as $name => $data) {
                $schedule = PhoneSchedule::tryFromArray((string) $name, $data);

                if ($schedule !== null) {
                    $valid[] = $schedule;
                }
            }

            if ($valid !== []) {
                $parsed[(string) $scope] = $valid;
            }
        }

        return $parsed;
    }

    /**
     * Minuto actual del día en la zona horaria del horario.
     */
    private function currentMinute(PhoneSchedule $schedule): int
    {
        $now = Date::now($schedule->timezone ?? $this->timezone);

        return $now->hour * 60 + $now->minute;
    }

    /**
     * Hora forzada (HH:MM) desde el query string, cuando el sitio lo permite.
     */
    private function timeOverride(Request $request): ?int
    {
        return $this->timeOverrideParam === null
            ? null
            : PhoneSchedule::minuteOfDay($request->query($this->timeOverrideParam));
    }

    /**
     * Obtiene los números de un idioma; si no tiene, usa los del idioma principal.
     *
     * @return array{0: Locale, 1: array<string, mixed>} Idioma efectivo y sus números.
     */
    private function localePhones(Locale $locale, ?Location $location): array
    {
        $phones = $this->scopePhones($location);

        foreach ([$locale, $this->defaultLocale] as $candidate) {
            if (is_array($phones[$candidate->value] ?? null) && $phones[$candidate->value] !== []) {
                return [$candidate, $phones[$candidate->value]];
            }
        }

        return [$locale, []];
    }

    /**
     * Obtiene el mapa de números de una location, o el mapa completo en sitios de un solo hotel.
     *
     * @return array<string, mixed>
     */
    private function scopePhones(?Location $location): array
    {
        $phones = $location === null
            ? $this->phones
            : ($this->phones[$location->value] ?? []);

        return is_array($phones) ? $phones : [];
    }

    private function scope(?Location $location): ?Location
    {
        return $this->locationScoped ? $location : null;
    }
}
