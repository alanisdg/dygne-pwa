<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/app/{any?}', function () {
    return view('app');
})->where('any', '.*');
