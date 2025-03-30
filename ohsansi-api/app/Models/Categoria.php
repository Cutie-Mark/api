<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'minimo_grado', 'maximo_grado'];

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_categoria');
    }

}
