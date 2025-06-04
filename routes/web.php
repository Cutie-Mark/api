<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Si no es /api/*, devuelve el index.html generado por React
Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');
