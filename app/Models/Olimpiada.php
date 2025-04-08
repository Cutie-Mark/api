<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Olimpiada extends Model
{
    use HasFactory;

    // Tabla asociada (opcional si sigue el nombre por convención)
    protected $table = 'olimpiadas';

    // Campos que se pueden asignar de forma masiva (mass assignment)
    protected $fillable = [
        'nombre',
        'gestion',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // Convertir fechas automáticamente a objetos Carbon
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function inscripciones() {
        return $this->hasMany(Inscripcion::class, 'id_olimpiada');
    }


    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_olimpiadas');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_olimpiadas');
    }

    public function cronogramas()
    {
        return $this->hasMany(Cronograma::class);
    }

    public function getFechaInicioAttribute($value)
    {
        return Carbon::parse($value)->toDateString(); // devuelve solo 'YYYY-MM-DD'
    }

    public function getFechaFinAttribute($value)
    {
        return Carbon::parse($value)->toDateString();
    }
};
