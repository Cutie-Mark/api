<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Servicio;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        return Rol::with('servicios')->skip(1)->get();
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
                    'nombre.unique' => 'El nombre del rol ingresado ya existe, intente con uno nuevo.',
                ]
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $rol = Rol::create(['nombre' => $request->nombre]);

            return response()->json([
                'id' => $rol->id,
                'nombre' => $rol->nombre
            ], 201);

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
                'roles_add' => 'array',
                'roles_add.*' => 'exists:roles,id',
                'roles_remove' => 'array',
                'roles_remove.*' => 'exists:roles,id',
            ]);

            $usuario = Usuario::findOrFail($request->usuario_id);

            if (!empty($request->roles_add)) {
                $usuario->roles()->syncWithoutDetaching($request->roles_add);
            }

            if (!empty($request->roles_remove)) {
                $usuario->roles()->detach($request->roles_remove);
            }

            return response()->json(['message' => 'Rol/es asignados exitosamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al asignar rol al usuario.'], 500);
        }
    }

    public function setServiciosRol(Request $request)
    {
        try {
            $request->validate([
                'rol_id' => 'required|exists:roles,id',
                'servicios_add' => 'array',
                'servicios_add.*' => 'exists:servicios,id',
                'servicios_remove' => 'array',
                'servicios_remove.*' => 'exists:servicios,id',
            ]);

            $rol = Rol::findOrFail($request->rol_id);

            if (!empty($request->servicios_add)) {
                $rol->servicios()->syncWithoutDetaching($request->servicios_add);
            }

            if (!empty($request->servicios_remove)) {
                $rol->servicios()->detach($request->servicios_remove);
            }

            return response()->json(['message' => 'Se asignaron los privilegios exitosamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al asignar servicios al rol.'], 500);
        }
    }
}
