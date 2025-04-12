<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;
    
    protected $table = 'areas';

    protected $fillable = ['nombre', 'vigente'];

    protected $hidden = ['pivot','created_at', 'updated_at'];

    // Relación con niveles de competencia (tabla intermedia)
    public function nivelesCompetencia()
    {
        return $this->hasMany(NivelCompetencia::class); // Relación con la tabla intermedia
    }

    // Relación con categorias a través de la tabla intermedia
    public function categorias()
    {
        return $this->hasManyThrough(Categoria::class, NivelCompetencia::class, 'area_id', 'id', 'id', 'categoria_id');
    }

    // Relación con olimpiadas a través de la tabla intermedia
    public function olimpiadas()
    {
        return $this->hasManyThrough(Olimpiada::class, NivelCompetencia::class, 'area_id', 'id', 'id', 'olimpiada_id');
    }
}


