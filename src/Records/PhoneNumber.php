<?php

namespace TAFER\Core\Records;

/**
 * Immutable value object that represents a phone number used by the frontend.
 *
 * The raw number is stored separately from the generated `tel:` URL so the
 * display text and link can be handled consistently across the application.
 */
readonly class PhoneNumber
{
    /**
     * Phone URL used in anchor tags.
     *
     * Example:
     * tel:+18009843015
     */
    public string $url;

    /**
     * Create a new phone number record.
     *
     * @param  string  $number  Raw phone number used for the `tel:` URL.
     *                          Example: +18009843015
     * @param  string  $buttonText  Human-readable phone number.
     *                              Example: +1 800 984 3015
     */
    public function __construct(
        public string $number,
        public string $buttonText,
    ) {
        $this->url = 'tel:'.$this->number;
    }

    /**
     * Convert the phone number to the array format expected by the frontend.
     *
     * @return array{
     *     url: string,
     *     button_text: string
     * }
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'button_text' => $this->buttonText,
        ];
    }
}
