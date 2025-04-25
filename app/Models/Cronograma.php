<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cronograma extends Model
{
    use HasFactory;

    protected $table = 'cronogramas';

    protected $fillable = [
        'tipo_plazo',
        'fecha_inicio',
        'fecha_fin',
        'olimpiada_id',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public $timestamps = false;

    public function olimpiada()
    {
        return $this->belongsTo(Olimpiada::class);
    }

    public function fase()
    {
        return $this->belongsTo(Fase::class, 'id_fase');
    }

}
