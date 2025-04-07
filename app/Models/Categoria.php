<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'minimo_grado', 'maximo_grado'];

    protected $hidden = ['pivot','created_at', 'updated_at'];
    
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_categoria');
    }

    public function olimpiadas()
    {
        return $this->belongsToMany(Olimpiada::class, 'categoria_olimpiadas');
    }

}
