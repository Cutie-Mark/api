<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    use HasFactory;

    protected $table = 'provincias';

    protected $fillable = ['nombre', 'departamento_id'];

    // Relación con Departamento
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    //Acceder a todos los postulantes de una provincia
    public function postulantes(){
        return $this->hasMany(Postulante::class);
    }
}
