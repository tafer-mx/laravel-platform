<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/hello-world');
});

Route::get('/hello-world', function () {
    return view('workbench::hello-world');
});
