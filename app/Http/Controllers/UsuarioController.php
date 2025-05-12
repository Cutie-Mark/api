<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'nombre_usuario' => 'required|string|unique:usuarios,nombre_usuario',
                    'password' => 'required|string',
                ],
                [
                    'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
                    'nombre_usuario.unique' => 'Ese nombre de usuario ya está en uso.',
                    'password.required' => 'La contraseña es obligatoria.',
                ]
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $usuario = Usuario::create([
                'nombre_usuario' => $request->nombre_usuario,
                'password' => $request->password, 
            ]);

            return response()->json([
                'Usuario creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error al crear usuario: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear el usuario', 'message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $usuarios = Usuario::with('roles:id,nombre') 
                            ->select('id', 'nombre_usuario')
                            ->skip(1)
                            ->get();
        return response()->json($usuarios);
    }

}
