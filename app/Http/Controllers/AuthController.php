<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'nombre_usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::where('nombre_usuario', $request->nombre_usuario)->first();


        // Verificar si el usuario no existe
        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        }

        // Verificar si la contraseña es correcta
        if (!Hash::check($request->password, $usuario->password)) {
            return response()->json(['error' => 'Credenciales incorrectas.'], 401);
        }

        $token = $usuario->createToken('token_acceso')->plainTextToken;

        $accesos = $usuario->roles()
            ->with('servicios')
            ->get()
            ->flatMap
            ->servicios
            ->pluck('nombre')
            ->unique()
            ->values();

        return response()->json([
                'usuario' => $usuario->nombre_usuario,
                'token' => $token,
                'roles' => $usuario->roles()->pluck('nombre'),
                'accesos' => $accesos,
            ]);
        }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
