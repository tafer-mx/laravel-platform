<?php

namespace TAFER\Core\Records;

use DateTimeZone;
use TAFER\Core\Enums\Locale;

/**
 * Ventana de horario en la que un grupo de teléfonos reemplaza a los normales.
 *
 * Ejemplo: un call center fuera de horario (Bookline) que atiende de 23:00 a 06:00.
 * Se admiten ventanas que cruzan la medianoche (`from` mayor que `to`).
 */
final readonly class PhoneSchedule
{
    /**
     * @param  string  $name  Identificador del horario. Ej: bookline.
     * @param  int  $from  Inicio de la ventana, en minutos desde la medianoche (incluido).
     * @param  int  $to  Fin de la ventana, en minutos desde la medianoche (no incluido).
     * @param  array<string, PhoneNumber>  $phones  Teléfonos por valor de idioma.
     * @param  string|null  $timezone  Zona horaria IANA de la ventana. Null usa la del sitio.
     */
    public function __construct(
        public string $name,
        public int $from,
        public int $to,
        public array $phones,
        public ?string $timezone = null,
    ) {}

    /**
     * Crea un horario a partir del mapa de números del sitio.
     *
     * Formato esperado:
     *   [
     *       'enabled' => true,
     *       'from' => '23:00',
     *       'to' => '06:00',
     *       'timezone' => 'America/Mexico_City', // opcional
     *       'phones' => [
     *           'es' => ['url' => 'tel:+523229800519', 'button_text' => '322 980 0519'],
     *       ],
     *   ]
     *
     * @param  string  $name  Identificador del horario.
     * @param  mixed  $data  Configuración del horario.
     * @return self|null Horario, o null si está desactivado o no es válido.
     */
    public static function tryFromArray(string $name, mixed $data): ?self
    {
        if (! is_array($data) || ! filter_var($data['enabled'] ?? true, FILTER_VALIDATE_BOOL)) {
            return null;
        }

        $from = self::minuteOfDay($data['from'] ?? null);
        $to = self::minuteOfDay($data['to'] ?? null);
        $timezone = $data['timezone'] ?? null;

        if ($from === null || $to === null || $from === $to || ! self::validTimezone($timezone)) {
            return null;
        }

        $phones = array_filter(array_map(
            fn (mixed $phone): ?PhoneNumber => PhoneNumber::tryFromArray($phone),
            is_array($data['phones'] ?? null) ? $data['phones'] : [],
        ));

        return $phones === [] ? null : new self($name, $from, $to, $phones, $timezone);
    }

    /**
     * Convierte una hora "HH:MM" en minutos desde la medianoche.
     *
     * @param  mixed  $time  Hora en formato 24 h. Ej: 23:00, 6:30.
     * @return int|null Minutos desde la medianoche, o null si el formato no es válido.
     */
    public static function minuteOfDay(mixed $time): ?int
    {
        if (! is_string($time) || preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($time), $matches) !== 1) {
            return null;
        }

        return ((int) $matches[1]) * 60 + (int) $matches[2];
    }

    /**
     * Indica si la ventana incluye un minuto del día.
     *
     * @param  int  $minute  Minutos desde la medianoche, en la zona horaria del horario.
     */
    public function covers(int $minute): bool
    {
        return $this->from < $this->to
            ? $minute >= $this->from && $minute < $this->to
            : $minute >= $this->from || $minute < $this->to;
    }

    /**
     * Obtiene el teléfono del horario para un idioma.
     */
    public function phoneFor(Locale $locale): ?PhoneNumber
    {
        return $this->phones[$locale->value] ?? null;
    }

    private static function validTimezone(mixed $timezone): bool
    {
        return $timezone === null
            || (is_string($timezone) && in_array($timezone, DateTimeZone::listIdentifiers(), true));
    }
}
