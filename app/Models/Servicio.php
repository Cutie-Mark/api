<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Servicio extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'servicio_rol');
    }
}
