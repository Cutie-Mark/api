<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/protegida', function () {
    return response()->json(['message' => '¡Esta ruta está protegida por CSRF!']);
});

Route::get('/hola', function () {
    return 'HOLA';
});


Route::post('/test-post', function () {
    return response()->json(['message' => 'POST request received']);
});

// Crear un área
Route::post('/areas', [AreaController::class, 'store']);

// Obtener todas las áreas
Route::get('/areas', [AreaController::class, 'index']);

// Actualizar un área por ID
// Route::put('/areas/{id}', [AreaController::class, 'update']);

// Eliminar un área por ID
Route::delete('/areas/{id}', [AreaController::class, 'destroy']);

// Crear una olimpiada
Route::post('/olimpiadas', [OlimpiadaController::class, 'store']);

// Obtener todas las áreas
Route::get('/olimpiadas', [OlimpiadaController::class, 'index']);

// Actualizar un área por ID
Route::put('/olimpiadas/{id}', [OlimpiadaController::class, 'update']);

// Eliminar un área por ID
Route::delete('/olimpiadas/{id}', [OlimpiadaController::class, 'destroy']);

