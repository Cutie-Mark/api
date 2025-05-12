<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Usuario;
use App\Models\Servicio;

class Rol extends Model
{
    protected $table = 'roles'; 
    
    protected $fillable = ['nombre'];

    protected $hidden = ['created_at', 'updated_at','pivot'];

    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'rol_usuario');
    }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'servicio_rol');
    }

}
