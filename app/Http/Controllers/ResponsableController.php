<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResponsableController extends Controller
{
    /**
     * Registra un nuevo responsable
     */
    public function crear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:255',
            'ci' => 'required|string|max:10|unique:responsables',
            'email' => 'required|email|unique:responsables',
            'telefono' => 'required|string|max:8'
        ], [
            'required' => 'El campo :attribute es obligatorio',
            'email.unique' => 'Ya existe una cuenta registrada con el correo',
            'ci.unique' => 'Ya existe una cuenta registrada con el ci'
        ])->setAttributeNames([
            'nombre_completo' => 'Nombre Completo',
            'ci' => 'CI',
            'email' => 'Email',
            'telefono' => 'Telefono'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $data = $validator->validated();
        $data['nombre_completo'] = ucwords(strtolower($data['nombre_completo'])); // Formato: Capitalizado

        $responsable = Responsable::create($data);

        return response()->json([
            'mensaje' => 'Registro de responsable exitoso',
            'data' => $responsable
        ], 201);
    }

    /**
     * Obtener todos los responsables
     */
    public function listar()
    {
        $responsables = Responsable::all();

        return response()->json([
            'data' => $responsables
        ], 200);
    }

    /**
     * Obtener responsable por id
     */
    public function mostrar($id)
    {
        $responsable = Responsable::find($id);

        if (!$responsable) {
            return response()->json([
                'error' => 'Responsable no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $responsable
        ], 200);
    }

    /**
     * Actualizar responsable por id
     */
    public function actualizar(Request $request, $id)
    {
        $responsable = Responsable::find($id);

        if (!$responsable) {
            return response()->json([
                'error' => 'Responsable no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'sometimes|required|string|max:255',
            'ci' => 'sometimes|required|string|max:15|unique:responsables,ci,' . $id,
            'email' => 'sometimes|required|email|unique:responsables,email,' . $id,
            'telefono' => 'sometimes|required|string|max:8'
        ], [
            'required' => 'El campo :attribute es obligatorio',
            'email.unique' => 'Ya existe una cuenta registrada con el correo',
            'ci.unique' => 'Ya existe una cuenta registrada con el ci'
        ])->setAttributeNames([
            'nombre_completo' => 'Nombre Completo',
            'ci' => 'CI',
            'email' => 'Email',
            'telefono' => 'Telefono'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['nombre_completo'])) {
            $data['nombre_completo'] = ucwords(strtolower($data['nombre_completo']));
        }

        $responsable->update($data);

        return response()->json([
            'mensaje' => 'Responsable actualizado correctamente',
            'data' => $responsable
        ], 200);
    }

    public function mostrarPorCi($ci)
    {
        // Buscar responsable por CI
        $responsable = Responsable::where('ci', $ci)->first();

        if (!$responsable) {
            return response()->json([
                'error' => 'Responsable no encontrado'
            ], 404);
        }

        // Devolver solo los campos requeridos
        return response()->json([
            'data' => [
                'ci' => $responsable->ci,
                'nombre_completo' => $responsable->nombre_completo,
                'email' => $responsable->email,
                'telefono' => $responsable->telefono
            ]
        ], 200);
    }

}