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
use App\Http\Controllers\PostulanteController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\InscripcionController;

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



// Obtener todos los departamentos
Route::get('departamentos', [DepartamentoController::class, 'index']); 
// Obtener un departamento por ID
Route::get('departamentos/{id}', [DepartamentoController::class, 'show']); 

Route::get('departamentos/{id}/provincias', [DepartamentoController::class, 'getProvinciasByDepartamento']);

Route::get('departamentos-con-provincias', [DepartamentoController::class, 'getAllDepartamentosWithProvincias']);


// Obtener todas las provincias
Route::get('provincias', [ProvinciaController::class, 'index']); 

// Obtener una provincia por ID
Route::get('provincias/{id}', [ProvinciaController::class, 'show']);


// =========================
//          POSTULANTE
// =========================
// Rutas RESTful estándar para Postulante (Listar todos, crear nuevo, mostrar uno)
Route::apiResource('postulantes', PostulanteController::class)->only(['index', 'store', 'show' ]);

// =========================
//          RESPONSABLE
// =========================
Route::prefix('responsables')->group(function () {
    Route::get('/', [ResponsableController::class, 'index']);
    Route::post('/', [ResponsableController::class, 'store']);
    Route::get('/{responsable}', [ResponsableController::class, 'show']);
    Route::get('/listas-inscripciones', [ResponsableController::class, 'listasConInscripciones']);
});

// =========================
//          LISTA
// =========================
// Grupo de rutas para listas
Route::prefix('listas')->group(function () {
    // Obtener todas las listas
    Route::get('/', [ListaController::class, 'index']);
    
    // Crear lista
    Route::post('/', [ListaController::class, 'store']);
    
    // Mostrar detalles de una lista
    Route::get('/{lista}', [ListaController::class, 'show']);
    
    // Actualizar lista
    Route::put('/{lista}', [ListaController::class, 'update']);
    
    // Eliminar lista
    Route::delete('/{lista}', [ListaController::class, 'destroy']);
    
    // Filtros adicionales
    Route::get('/responsable/{responsable}', [ListaController::class, 'porResponsable']);
    Route::get('/codigo/{codigo}', [ListaController::class, 'porCodigo']);
});
// =========================
//          INSCRIPCION
// =========================
// Grupo de rutas para inscripciones
Route::prefix('inscripciones')->group(function () {
    // Obtener todas las inscripciones
    Route::get('/', [InscripcionController::class, 'index']);
    
    // Crear inscripción masiva
    Route::post('/', [InscripcionController::class, 'store']);
    
    // Mostrar detalles de una inscripción
    Route::get('/{inscripcion}', [InscripcionController::class, 'show']);
    
    // Actualizar estado de una inscripción
    Route::put('/{inscripcion}/estado', [InscripcionController::class, 'updateEstado']);
    
    // Eliminar inscripción
    Route::delete('/{inscripcion}', [InscripcionController::class, 'destroy']);
    
    // Filtros adicionales
    Route::get('/estado/{estado}', [InscripcionController::class, 'filtrarPorEstado']);
    Route::get('/responsable/{responsable}', [InscripcionController::class, 'filtrarPorResponsable']);
});


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