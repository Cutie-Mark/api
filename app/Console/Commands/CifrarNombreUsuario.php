<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;
use Illuminate\Support\Facades\Crypt;

class CifrarNombresUsuario extends Command
{
    protected $signature = 'usuarios:cifrar-nombres';
    protected $description = 'Cifra los nombres de usuario si no están cifrados aún';

    public function handle()
    {
        $usuarios = Usuario::all();
        $actualizados = 0;

        foreach ($usuarios as $usuario) {
            $rawNombre = $usuario->getRawOriginal('nombre_usuario');

            try {
                // Si no lanza excepción, ya está cifrado
                Crypt::decryptString($rawNombre);
                $this->info("Ya cifrado: {$usuario->id}");
            } catch (\Exception $e) {
                // Si lanza excepción, entonces no está cifrado y lo ciframos
                $usuario->nombre_usuario = $rawNombre; // el mutator lo cifrará
                $usuario->save();
                $this->info("Cifrado: {$usuario->id}");
                $actualizados++;
            }
        }

        $this->info("Proceso completado. Usuarios actualizados: $actualizados");
        return 0;
    }
}
