<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Responsable extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'nombre_completo', 
        'ci',
        'email', 
        'telefono'
    ];

    public function listas()
    {
        return $this->hasMany(Lista::class, 'responsable_id');
    }

    public function inscripciones()
    {
        return $this ->hasMany(Inscripcion::class, 'responsable_id');
    }
}