<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaCategoria extends Model
{
    use HasFactory;

    protected $table = 'area_categoria';
    public $timestamps = true;
    protected $fillable = ['area_id', 'categoria_id'];

    // Relaciones con Area y Categoria
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}
