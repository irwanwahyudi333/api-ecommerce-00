<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/scalar', function () {
    return view('scalar');
})->name('scalar.docs');

Route::get('/scalar', function () {
    return redirect('/docs/scalar');
});
