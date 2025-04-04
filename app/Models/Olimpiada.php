<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // Opcional: convertir fechas automáticamente a objetos Carbon
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_olimpiadas');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_olimpiadas');
    }
}
