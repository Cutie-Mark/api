<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ColegioController extends Controller
{
    /**
     * Registrar un nuevo colegio (Store)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $nombre = $this->formatoCaseNombre($request->nombre);
        $this->checkNombreUnico($nombre);

        $colegio = Colegio::create(['nombre' => $nombre]);

        return response()->json([
            'mensaje' => 'Colegio creado con éxito',
            'colegio' => $colegio
        ], 201);
    }

    /**
     * Listar todos los colegios (Index)
     */
    public function index()
    {
        return response()->json(Colegio::all());
    }

    /**
     * Mostrar un colegio por ID (Show)
     */
    public function show($id)
    {
        try {
            $colegio = Colegio::findOrFail($id);
            return response()->json($colegio);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Colegio no encontrado'], 404);
        }
    }

    /**
     * Actualizar un colegio por ID (Update)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        try {
            $colegio = Colegio::findOrFail($id);

            $nombre = $this->formatoCaseNombre($request->nombre);
            $this->checkNombreUnico($nombre, $id);

            $colegio->update(['nombre' => $nombre]);

            return response()->json([
                'mensaje' => 'Nombre de Colegio actualizado con éxito',
                'colegio' => $colegio
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Colegio no encontrado'], 404);
        }
    }

    /**
     * Formatea un nombre a Title Case: primera letra mayúscula, resto minúscula
     */
    private function formatoCaseNombre(string $valor): string
    {
        return collect(explode(' ', $valor))
            ->map(fn($word) => ucfirst(strtolower($word)))
            ->implode(' ');
    }

    /**
     * Verifica unicidad case-insensitive, opcionalmente excluyendo un ID
     */
    private function checkNombreUnico(string $valor, ?int $ignoreId = null): void
    {
        $query = Colegio::whereRaw('LOWER(nombre) = ?', [strtolower($valor)]);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new HttpResponseException(response()->json([
                'error' => 'Ya existe un colegio con ese nombre'
            ], 409));
        }
    }
}