<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $fillable = ['nombre'];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'rol_usuario');
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'servicio_rol');
    }
}
