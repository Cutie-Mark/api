<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Crypt;
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
        return self::all()->first(function ($user) use ($nombre) {
            try {
                return Crypt::decryptString($user->getRawOriginal('nombre_usuario')) === $nombre;
            } catch (\Exception $e) {
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
