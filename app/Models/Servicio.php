<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Servicio extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'servicio_rol');
    }
}
