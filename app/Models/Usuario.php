<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log; // Add this line
use App\Models\Rol;


class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['nombre_usuario', 'password'];

    protected $hidden = ['password', 'remember_token','pivot'];

    // Hashear la contraseña al asignarla
    public function setPasswordAttribute($value)
    {
        if (strlen($value) === 60 && substr($value, 0, 4) === '$2y$') {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function setNombreUsuarioAttribute($value)
    {
        $this->attributes['nombre_usuario'] = Crypt::encryptString($value);
    }

    public function getNombreUsuarioAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    public function getAuthIdentifierName()
    {
        return 'nombre_usuario';  // Establecemos que se use nombre_usuario en vez de email.
    }

    public static function findByNombreUsuario($nombre)
    {
        $allUsers = self::all();
        Log::info('Searching for user: ' . $nombre);
        Log::info('Total users found in DB: ' . $allUsers->count());

        return $allUsers->first(function ($user) use ($nombre) {
            try {
                $rawNombreUsuario = $user->getRawOriginal('nombre_usuario');
                Log::info('Attempting to decrypt user ID: ' . $user->id . ', Raw nombre_usuario: ' . $rawNombreUsuario);
                $decryptedName = Crypt::decryptString($rawNombreUsuario);
                Log::info('Decrypted name: ' . $decryptedName);
                return $decryptedName === $nombre;
            } catch (\Exception $e) {
                Log::error('Decryption failed for user ID: ' . $user->id . ' - Error: ' . $e->getMessage());
                return false;
            }
        });
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_usuario', 'usuario_id', 'rol_id');
    }

    public function checkAcceso(string $servicioNombre): bool
    {
        return $this->roles->flatMap->servicios->contains('nombre', $servicioNombre);
    }

}
