<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'area_categoria');
    }
}


