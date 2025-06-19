<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'nombre_usuario' => 'required|string',
                'password' => 'required|string',
            ]);

            $usuario = Usuario::findByNombreUsuario($request->nombre_usuario);

            // Verificar si el usuario no existe
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado.'], 401);
            }

            // Verificar si la contraseña es correcta
            if (!Hash::check($request->password, $usuario->password)) {
                return response()->json(['error' => 'Credenciales incorrectas.'], 401);
            }

            $token = $usuario->createToken('token_acceso')->plainTextToken;

            $roles = $usuario->roles()->with('servicios')->get();

            $accesos = $roles->flatMap(function ($rol) {
                return $rol->servicios ?? collect();
            })->pluck('nombre')->unique()->values();

            return response()->json([
                'usuario' => $usuario->nombre_usuario,
                'token' => $token,
                'roles' => $roles->pluck('nombre'),
                'accesos' => $accesos,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error inesperado en el servidor.'], 500);
        }

    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
