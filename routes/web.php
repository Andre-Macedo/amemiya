<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

Route::get('/', function () {
    return view('welcome');
});
