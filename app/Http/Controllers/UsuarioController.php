<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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
                    'password.required' => 'La contraseña es obligatoria.',
                ]
            );

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $existe = Usuario::all()->contains(function ($user) use ($request) {
                try {
                    return Crypt::decryptString($user->getRawOriginal('nombre_usuario')) === $request->nombre_usuario;
                } catch (\Exception $e) {
                    return false;
                }
            });

            if ($existe) {
                return response()->json(['errors' => ['nombre_usuario' => ['Ese nombre de usuario ya está en uso.']]], 400);
            }

            $usuario = Usuario::create([
                'nombre_usuario' => $request->nombre_usuario,
                'password' => $request->password, 
            ]);

            return response()->json([
                'Usuario creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el usuario', 'message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $usuarios = Usuario::with('roles:id,nombre') 
                            ->select('id', 'nombre_usuario')
                            ->skip(1)
                            ->get()
                            ->map(function ($usuario) {
                                try {
                                    $usuario->nombre_usuario = Crypt::decryptString($usuario->getRawOriginal('nombre_usuario'));
                                } catch (\Exception $e) {
                                    $usuario->nombre_usuario = null; 
                                }
                                return $usuario;
                            });
        return response()->json($usuarios);
    }

}
