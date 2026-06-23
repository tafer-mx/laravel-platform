<?php

use Illuminate\Support\Facades\Route;
use TAFER\Core\Enums\Device;
use TAFER\Core\Enums\Locale;
use TAFER\Core\Enums\Location;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Http\Controllers\StoryblokWebhookController;
use TAFER\Core\Middlewares\ResolveRequestCtx;
use TAFER\Core\Phones\GarzaBlancaPhoneDirectory;
use Workbench\App\Http\Controllers\StoryblokPageController;

Route::get('/', function () {
    return redirect('/home-villa-palmar-cancun');
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

Route::post('/storyblok/webhook', StoryblokWebhookController::class);

Route::get('/{slug?}', StoryblokPageController::class)
    ->where('slug', '.*')
    ->middleware(ResolveRequestCtx::class);
