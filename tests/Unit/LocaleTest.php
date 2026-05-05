<?php

use TAFER\Core\Enums\Locale;

it('has the expected locale values', function () {
    expect(Locale::English->value)->toBe('en')
        ->and(Locale::Spanish->value)->toBe('es');
});

it('gets locale labels', function () {
    expect(Locale::English->label())->toBe('English')
        ->and(Locale::Spanish->label())->toBe('Spanish');
});