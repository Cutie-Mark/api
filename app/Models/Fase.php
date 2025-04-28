<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fase extends Model
{
    use HasFactory;

    protected $table = 'fases';

    protected $fillable = [
        'nombre_fase',
        'orden'
    ];

    public function cronogramas()
    {
        return $this->hasMany(Cronograma::class, 'id_fase');
    }
}
