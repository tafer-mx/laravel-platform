<?php

use Illuminate\Support\Facades\Route;
use TAFER\Core\Enums\Resort;
use TAFER\Core\Http\Controllers\StoryblokWebhookController;
use TAFER\Core\Middlewares\ResolveRequestCtx;
use Workbench\App\Http\Controllers\StoryblokPageController;

Route::get('/', function () {
    return redirect('/home-villa-palmar-cancun');
});

Route::get('/hello-world', function () {
    return view('tafer::hello-world', ['resort' => Resort::GarzaBlanca]);
});

Route::post('/storyblok/webhook', StoryblokWebhookController::class);

Route::get('/{slug?}', StoryblokPageController::class)
    ->where('slug', '.*')
    ->middleware(ResolveRequestCtx::class);
