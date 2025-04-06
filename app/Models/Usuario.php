<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['nombre_usuario', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Hashear la contraseña al asignarla
    public function setPasswordAttribute($value)
    {
        if (strlen($value) === 60 && substr($value, 0, 4) === '$2y$') {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    public function getAuthIdentifierName()
    {
        return 'nombre_usuario';  // Establecemos que se use nombre_usuario en vez de email.
    }
}
