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
        'precio_inscripcion',
        'limite_inscripciones',        
        'url_plantilla', 
        'descripcion_convocatoria', 
    ];

    protected $hidden = ['created_at', 'updated_at'/*, 'url_plantilla'*/];

    // Convertir fechas automáticamente a objetos Carbon
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    protected $appends = ['fase'];

    protected $attributes = [
        'descripcion_convocatoria' => '',
    ];

    public function inscripciones() {
        return $this->hasMany(Inscripcion::class, 'id_olimpiada');
    }


    // Relación con áreas a través de la tabla intermedia
    public function areas()
    {
        return $this->hasManyThrough(Area::class, NivelCompetencia::class, 'olimpiada_id', 'id', 'id', 'area_id');
    }

    // Relación con categorías a través de la tabla intermedia
    public function categorias()
    {
        return $this->hasManyThrough(Categoria::class, NivelCompetencia::class, 'olimpiada_id', 'id', 'id', 'categoria_id');
    }

    // Agregar o afinar campos
    public function cronogramas()
    {
        return $this->hasMany(Cronograma::class);
    }

    public function getFechaInicioAttribute($value)
    {
        return Carbon::parse($value)->toDateString(); 
    }

    public function getFechaFinAttribute($value)
    {
        return Carbon::parse($value)->toDateString();
    }

    public function listas()
    {
        return $this->hasMany(Lista::class, 'olimpiada_id');
    }

    public function getFaseAttribute()
    {
        $hoy = now()->toDateString();

        return $this->cronogramas()
            ->with('fase') 
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->orderBy('fecha_inicio') 
            ->first();
    }

};
