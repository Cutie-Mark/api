<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rol extends Model
{
    protected $table = 'roles'; 
    
    protected $fillable = ['nombre'];

    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'rol_usuario');
    }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'servicio_rol');
    }

}
