<?php

use TAFER\Core\Records\PhoneNumber;
use TAFER\Core\Records\PhoneSchedule;
use TAFER\Core\Support\PhoneTextHelper;

it('replaces only the phone number in a text and drops the leading plus', function () {
    expect(PhoneTextHelper::replaceNumber('Tel. +52 800 323 0581', '+52 800 649 0478'))
        ->toBe('Tel. 52 800 649 0478')
        ->and(PhoneTextHelper::replaceNumber('Call us', '+1 800 931 6063'))
        ->toBe('Call us');
});

it('creates a phone number from the frontend array format', function () {
    $phone = PhoneNumber::tryFromArray(['url' => 'tel:+18009316063', 'button_text' => '+1 800 931 6063']);

    expect($phone->number)->toBe('+18009316063')
        ->and($phone->url)->toBe('tel:+18009316063')
        ->and($phone->buttonText)->toBe('+1 800 931 6063');
});

it('returns null for incomplete phone data', function (mixed $data) {
    expect(PhoneNumber::tryFromArray($data))->toBeNull();
})->with([
    'null' => [null],
    'string' => ['tel:+18009316063'],
    'missing text' => [['url' => 'tel:+18009316063']],
    'empty url' => [['url' => '', 'button_text' => 'x']],
]);

it('parses schedule times in 24h format', function (mixed $time, ?int $minute) {
    expect(PhoneSchedule::minuteOfDay($time))->toBe($minute);
})->with([
    ['23:00', 1380],
    ['6:30', 390],
    ['00:00', 0],
    ['24:00', null],
    ['11pm', null],
    [null, null],
]);

it('rejects schedules with an invalid window or timezone', function (array $data) {
    expect(PhoneSchedule::tryFromArray('bookline', $data))->toBeNull();
})->with([
    'same start and end' => [['from' => '06:00', 'to' => '06:00', 'phones' => ['es' => ['url' => 'tel:+1', 'button_text' => '1']]]],
    'invalid timezone' => [['from' => '23:00', 'to' => '06:00', 'timezone' => 'Mars/Base', 'phones' => ['es' => ['url' => 'tel:+1', 'button_text' => '1']]]],
    'no phones' => [['from' => '23:00', 'to' => '06:00', 'phones' => []]],
]);
