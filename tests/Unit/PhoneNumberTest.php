<?php

    use TAFER\Core\Records\PhoneNumber;

    it('stores number and button text', function (): void {
        $phoneNumber = new PhoneNumber(
            number: '+18009843015',
            buttonText: '+1 800 984 3015',
        );

        expect($phoneNumber->number)->toBe('+18009843015')
            ->and($phoneNumber->buttonText)->toBe('+1 800 984 3015');
    });

    it('generates tel url from number', function (): void {
        $phoneNumber = new PhoneNumber(
            number: '+18009843015',
            buttonText: '+1 800 984 3015',
        );

        expect($phoneNumber->url)->toBe('tel:+18009843015');
    });

    it('converts to frontend array format', function (): void {
        $phoneNumber = new PhoneNumber(
            number: '+18009843015',
            buttonText: '+1 800 984 3015',
        );

        expect($phoneNumber->toArray())->toBe([
            'url' => 'tel:+18009843015',
            'button_text' => '+1 800 984 3015',
        ]);
    });