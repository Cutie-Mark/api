<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelCompetencia extends Model
{
    use HasFactory;

    protected $table = 'niveles_competencia';

    protected $fillable = [
        'categoria_id',
        'area_id',
        'olimpiada_id',
        'vigente', 
    ];

    protected $casts = [
        'vigente' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function olimpiada()
    {
        return $this->belongsTo(Olimpiada::class, 'olimpiada_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'nivel_competencia_id');
    }
}
