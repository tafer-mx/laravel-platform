<?php

namespace TAFER\Core\Support;

final class PhoneTextHelper
{
    /**
     * Encuentra un teléfono dentro de un texto. Ej: "+52 800 323 0581", "(800) 323-0581".
     */
    private const PHONE_PATTERN = '/\+?\d[\d\s\-()]{7,}/';

    /**
     * Reemplaza el teléfono dentro de un texto y conserva el texto que lo rodea.
     *
     * El `+` inicial se quita del texto visible; los enlaces `tel:` lo conservan.
     * Ej: ("Tel. +52 800 323 0581", "+52 800 649 0478") => "Tel. 52 800 649 0478".
     *
     * @param  string  $text  Texto que contiene el teléfono.
     * @param  string  $phone  Teléfono legible que se va a insertar.
     * @return string Texto con el teléfono reemplazado.
     */
    public static function replaceNumber(string $text, string $phone): string
    {
        return preg_replace(self::PHONE_PATTERN, ltrim($phone, '+'), $text) ?? $text;
    }
}
