<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Postulante extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'apellido', 'fecha_nacimiento', 'provincia_id', 'correo_postulante', 'ci', 'curso'];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

}