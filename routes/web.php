<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/app', function () {
    return Inertia::render('Dashboard');
})->name('app.dashboard');
