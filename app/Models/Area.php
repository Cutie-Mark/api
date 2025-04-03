<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;
    
    protected $table = 'areas';

    protected $fillable = ['nombre'];

    protected $hidden = ['pivot'];

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'area_categoria');
    }

    public function olimpiadas()
    {
        return $this->belongsToMany(Area::class, 'area_olimpiada');
    }
}


