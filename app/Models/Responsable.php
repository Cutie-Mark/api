<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsable extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'apellido', 'ci', 'email', 'telefono', 'es_profesor'];

    //Relacion con Listas
    public function listas()
    {
        return $this->hasMany(Lista::class);
    }

    /*public function inscripciones() { causara errores
        return $this->hasMany(Inscripcion::class);
    }*/ 
    
};
