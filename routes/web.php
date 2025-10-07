<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/js/tracker.js', [App\Http\Controllers\TrackerController::class, 'serve']);