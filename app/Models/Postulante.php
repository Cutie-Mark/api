<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Colegio;

class Postulante extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombres', 
        'apellidos', 
        'fecha_nacimiento', 
        'provincia_id', 
        'email', 
        'ci', 
        'curso'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];
    
    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'postulante_id');
    }

    public function colegio()
    {
        return $this->hasOneThrough(
            Colegio::class,
            Inscripcion::class,
            'postulante_id', // Foreign key on inscripciones table
            'id', // Foreign key on colegios table
            'id', // Local key on postulantes table
            'colegio_id' // Local key on inscripciones table
        );
    }
}