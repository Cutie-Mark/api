<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'minimo_grado', 'maximo_grado', 'vigente'];

    protected $hidden = ['pivot','created_at', 'updated_at'];
    
     // Relación con áreas a través de la tabla intermedia
     public function areas()
     {
         return $this->hasManyThrough(Area::class, NivelCompetencia::class, 'categoria_id', 'id', 'id', 'area_id');
     }
 
     // Relación con olimpiadas a través de la tabla intermedia
     public function olimpiadas()
     {
         return $this->hasManyThrough(Olimpiada::class, NivelCompetencia::class, 'categoria_id', 'id', 'id', 'olimpiada_id');
     }

}
