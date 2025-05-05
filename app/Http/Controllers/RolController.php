<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Servicio;

use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        return Rol::with('servicios')->get();
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'nombre' => 'required|string|unique:roles,nombre',
                ],
                [
                    'nombre.required' => 'El nombre del rol es obligatorio.',
                    'nombre.unique' => 'El nombre del rol ya existe, intente con uno nuevo.',
                ]
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $rol = Rol::create(['nombre' => $request->nombre]);

            return response()->json($rol, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el rol', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $rol = Rol::findOrFail($id);
            $rol->delete();

            return response()->json(['message' => 'Rol eliminado']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar el rol', 'message' => $e->getMessage()], 500);
        }
    }

    public function setRolUsuario(Request $request)
    {
        try {
            $request->validate([
                'usuario_id' => 'required|exists:usuarios,id',
                'rol_id' => 'required|exists:roles,id',
            ]);

            $usuario = Usuario::findOrFail($request->usuario_id);
            $usuario->roles()->syncWithoutDetaching([$request->rol_id]);

            return response()->json(['message' => 'Rol asignado al usuario'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al asignar rol al usuario.'], 500);
        }
    }

    public function setServiciosRol(Request $request, $rolId)
    {
        try {
            $request->validate([
                'rol_id' => 'required|exists:roles,id',
                'servicios' => 'required|array',
                'servicios.*' => 'exists:servicios,id',
            ]);

            $rol = Rol::findOrFail($request->rol_id);
            $rol->servicios()->sync($request->servicios);

            return response()->json(['message' => 'Servicios asignados al rol'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al asignar servicios al rol.'], 500);
        }
    }
}
