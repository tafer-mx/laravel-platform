<?php

namespace TAFER\Core\Contracts;

use Illuminate\Http\Request;
use TAFER\Core\Context\RequestCtx;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Records\PhoneNumber;

/**
 * Contrato para resolver el teléfono de reservaciones que ve el visitante.
 *
 * El mapa de números pertenece a cada sitio (ver `tafer.phones`); las
 * implementaciones solo aplican las reglas de resolución compartidas.
 */
interface PhoneResolver
{
    /**
     * Resuelve el teléfono para el visitante.
     *
     * El número de partner (del query string o de la cookie) gana sobre el número
     * del dispositivo cuando el idioma acepta partners y el sitio definió uno.
     *
     * @param  Request  $request  Request actual, para leer y guardar el partner.
     * @param  Locale  $locale  Idioma de la página.
     * @param  Device  $device  Dispositivo del visitante. La tablet cuenta como mobile.
     * @param  Location|null  $location  Location de la página. Se ignora si el sitio no es location scoped.
     * @return PhoneNumber|null Teléfono que corresponde, o null si no está configurado.
     */
    public function resolve(
        Request $request,
        Locale $locale,
        Device $device,
        ?Location $location = null,
    ): ?PhoneNumber;

    /**
     * Resuelve el teléfono con el idioma, el dispositivo y la location del contexto del request.
     *
     * @param  Request  $request  Request actual, para leer y guardar el partner.
     * @param  RequestCtx  $context  Contexto del request ya resuelto.
     * @return PhoneNumber|null Teléfono que corresponde, o null si no está configurado.
     */
    public function forContext(Request $request, RequestCtx $context): ?PhoneNumber;

    /**
     * Obtiene el teléfono base (desktop) de un idioma.
     *
     * Es el número que los editores escriben en el CMS para que el sitio lo cambie
     * por el que corresponde al dispositivo y al partner del visitante.
     *
     * @param  Locale  $locale  Idioma de la página.
     * @param  Location|null  $location  Location de la página. Se ignora si el sitio no es location scoped.
     * @return PhoneNumber|null Teléfono base, o null si no está configurado.
     */
    public function basePhone(Locale $locale, ?Location $location = null): ?PhoneNumber;
}
