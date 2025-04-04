<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cronograma extends Model
{
    use HasFactory;

    protected $table = 'cronograma';

    protected $fillable = [
        'tipo_plazo',
        'fecha_inicio',
        'fecha_fin',
        'olimpiada_id',
    ];

    public $timestamps = false;

    public function olimpiada()
    {
        return $this->belongsTo(Olimpiada::class);
    }
}
