<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\ProvinciaController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\OlimpiadaController;
use App\Http\Controllers\AreaCategoriaController;
use App\Http\Controllers\ColegioController;
use App\Http\Controllers\CronogramaController;



Route::get('/rutas', function () {
    return collect(Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
        ];
    })->filter(fn ($route) => str_starts_with($route['uri'], 'api/'))->values();
});


// Crear un área
Route::post('/areas', [AreaController::class, 'store']);

// Obtener todas las áreas
Route::get('/areas', [AreaController::class, 'index']);

// Actualizar un área por ID
// Route::put('/areas/{id}', [AreaController::class, 'update']);

// Eliminar un área por ID
Route::delete('/areas/{id}', [AreaController::class, 'destroy']);


// Crear una categoria
Route::post('/categorias', [CategoriaController::class, 'store']);

// Obtener todas las categorias
Route::get('/categorias', [CategoriaController::class, 'index']);

// Actualizar una categoria por ID
Route::put('/categorias/{id}', [CategoriaController::class, 'update']);

// Eliminar un categoria por ID
Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);


// Asignar una categoria a un area
Route::post('/categoria/area', [AreaCategoriaController::class, 'attachCategoriaToArea']);

// Desligar una categoria de un area
Route::delete('/categoria/area', [AreaCategoriaController::class, 'detachCategoriaFromArea']);

// Filtrar categorias de un area
Route::get('/areas/{id}/categorias', [AreaCategoriaController::class, 'findCategoriasByArea']);

// Filtrar categorias con sus areas
Route::get('/categorias/areas', [AreaCategoriaController::class, 'getAllCategoriasWithAreas']);

// Filtras las areas con sus categorias
Route::get('/areas/categorias', [AreaCategoriaController::class, 'getAllAreasWithCategorias']);

// Filtrar las areas segun cursos asociados
Route::get('/curso/{curso}/areas', [AreaCategoriaController::class, 'getAreasByCurso']);

// Filtrar las categorias segun el area y curso deseados
Route::get('/area/{area}/curso/{curso}/categorias', [AreaCategoriaController::class, 'getCategoriasByAreaCurso']);



// Crear una olimpiada
Route::post('/olimpiadas', [OlimpiadaController::class, 'store']);

// Obtener todas las olimpiadas
Route::get('/olimpiadas', [OlimpiadaController::class, 'index']);

// Actualizar las fechas de una olimpiadas
Route::put('/olimpiadas/{id}', [OlimpiadaController::class, 'update']);

// Eliminar una olimpiada por id
Route::delete('/olimpiadas/{id}', [OlimpiadaController::class, 'destroy']);

// Obtener todos los cronogramas
Route::get('/cronogramas', [CronogramaController::class, 'index']);

//Crear un plazo en el cronograma
Route::post('/cronogramas', [CronogramaController::class, 'store']);

//Actualizar un cronograma
Route::put('/cronogramas/{id}', [CronogramaController::class, 'update']);

// Borrar un plazo del cronograma 
Route::delete('/cronogramas/{id}', [CronogramaController::class, 'destroy']);



// Obtener todos los departamentos
Route::get('departamentos', [DepartamentoController::class, 'index']); 

// Obtener un departamento por ID
Route::get('departamentos/{id}', [DepartamentoController::class, 'show']); 

// Obtener todas las provincias
Route::get('provincias', [ProvinciaController::class, 'index']); 

// Obtener una provincia por ID
Route::get('provincias/{id}', [ProvinciaController::class, 'show']);

// Obtener provincias por departamento
Route::get('departamento/{departamentoId}/provincias', [DepartamentoController::class, 'getProvinciasByDepartamento']); 

// Obtener todos los departamentos con sus provincias
Route::get('departamentos/provincias', [DepartamentoController::class, 'getAllDepartamentosWithProvincias']); 


// Obtener todos los colegios
Route::get('colegios', [ColegioController::class, 'index']); 

// Registrar un nuevo colegio
Route::post('colegios', [ColegioController::class, 'store']); 

// Eliminar un colegio por ID
Route::delete('colegios/{id}', [ColegioController::class, 'destroy']); 









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