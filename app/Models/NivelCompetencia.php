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
        'vigente', // si estás manejando vigencia desde el principio
    ];

    protected $casts = [
        'vigente' => 'boolean',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function olimpiada()
    {
        return $this->belongsTo(Olimpiada::class);
    }
}
