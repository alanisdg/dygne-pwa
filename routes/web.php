<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/app', function () {
    return Inertia::render('Dashboard');
})->name('app.dashboard');

Route::get('/devices/{id}', function (string $id) {
    return Inertia::render('Device', ['id' => $id]);
})->name('devices.show'); 

Route::get('/notification/{id}', function (string $id) {
    return Inertia::render('Notification', ['id' => $id]);
})->name('notification.show');