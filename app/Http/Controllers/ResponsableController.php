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
            'ci' => 'required|string|max:10',
            'email' => 'required|email|unique:responsables',
            'telefono' => 'required|string|max:8'
        ], [
            'required' => 'El campo :attribute es obligatorio',
            'email.unique' => 'Ya existe una cuenta registrada con el correo'
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

        // Verificar si el CI ya existe (necesario ahora que está encriptado)
        $ciExists = false;
        $allResponsables = Responsable::all();
        foreach ($allResponsables as $existingResponsable) {
            if ($existingResponsable->ci === $request->ci) {
                $ciExists = true;
                break;
            }
        }

        if ($ciExists) {
            return response()->json([
                'error' => 'Ya existe una cuenta registrada con el ci'
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
            'ci' => 'sometimes|required|string|max:15',
            'email' => 'sometimes|required|email|unique:responsables,email,' . $id,
            'telefono' => 'sometimes|required|string|max:8'
        ], [
            'required' => 'El campo :attribute es obligatorio',
            'email.unique' => 'Ya existe una cuenta registrada con el correo'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        // Si se cambia el CI, verificar que no exista ya (necesario ahora que está encriptado)
        if ($request->has('ci') && $request->ci !== $responsable->ci) {
            $ciExists = false;
            $allResponsables = Responsable::where('id', '!=', $id)->get();
            foreach ($allResponsables as $existingResponsable) {
                if ($existingResponsable->ci === $request->ci) {
                    $ciExists = true;
                    break;
                }
            }

            if ($ciExists) {
                return response()->json([
                    'error' => 'Ya existe una cuenta registrada con el ci'
                ], 422);
            }
        }

        if ($request->has('nombre_completo')) {
            $request->merge(['nombre_completo' => ucwords(strtolower($request->nombre_completo))]);
        }

        $responsable->update($request->all());

        return response()->json([
            'mensaje' => 'Responsable actualizado correctamente',
            'data' => $responsable
        ], 200);
    }

    public function mostrarPorCi($ci)
    {
        // Buscar responsable por CI desencriptado
        $responsable = null;
        $allResponsables = Responsable::all();
        foreach ($allResponsables as $resp) {
            if ($resp->ci === $ci) {
                $responsable = $resp;
                break;
            }
        }

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