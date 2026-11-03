<?php

namespace TAFER\Core\Phones;

use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Http\Request;
use TAFER\Core\Enums\Location;

/**
 * Lee el id de partner del visitante y lo guarda en una cookie.
 *
 * El query string tiene prioridad sobre la cookie. Si llega un partner en el
 * query string y es distinto al guardado, se encola en la cookie.
 */
final readonly class PartnerTracker
{
    /**
     * @param  QueueingFactory  $cookies  Cookie jar para encolar la cookie del partner.
     * @param  string  $queryParam  Parámetro del query string que trae el id de partner.
     * @param  string  $cookieName  Nombre base de la cookie.
     * @param  int  $cookieMinutes  Duración de la cookie en minutos.
     */
    public function __construct(
        private QueueingFactory $cookies,
        private string $queryParam = 'partner',
        private string $cookieName = 'partner_id',
        private int $cookieMinutes = 43200,
    ) {}

    /**
     * Resuelve el id de partner del visitante.
     *
     * @param  Request  $request  Request actual.
     * @param  Location|null  $location  Location de la cookie, cuando el sitio es location scoped.
     * @return string|null Id numérico del partner, o null si no hay.
     */
    public function resolve(Request $request, ?Location $location = null): ?string
    {
        $cookieName = $this->cookieName($location);
        $cookiePartner = $this->partnerId($request->cookie($cookieName));
        $queryPartner = $this->partnerId($request->query($this->queryParam));

        if ($queryPartner === null) {
            return $cookiePartner;
        }

        if ($queryPartner !== $cookiePartner) {
            $this->cookies->queue($cookieName, $queryPartner, $this->cookieMinutes);
        }

        return $queryPartner;
    }

    /**
     * Obtiene el nombre de la cookie del partner. Los sitios location scoped usan una cookie por location.
     *
     * @param  Location|null  $location  Location de la cookie.
     * @return string Nombre de la cookie. Ej: partner_id, partner_id_cancun.
     */
    public function cookieName(?Location $location = null): string
    {
        return $location === null
            ? $this->cookieName
            : "{$this->cookieName}_{$location->value}";
    }

    private function partnerId(mixed $value): ?string
    {
        return is_string($value) && ctype_digit($value) ? $value : null;
    }
}
