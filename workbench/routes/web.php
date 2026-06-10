<?php

use Illuminate\Support\Facades\Route;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Phones\GarzaBlancaPhoneDirectory;

Route::get('/', function () {
    return redirect('/hello-world');
});

Route::get('/hello-world', function () {
    return view('tafer::hello-world', ['resort' => Resort::GarzaBlanca]);
});

Route::get('/phones', function () {
    $directory = new GarzaBlancaPhoneDirectory;

    return response()->json([
        'resort' => $directory->resort()->label(),
        'phones' => $directory->get(Location::Cancun, Locale::English, Device::Desktop),
    ]);
});
