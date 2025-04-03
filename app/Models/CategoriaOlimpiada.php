<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaOlimpiada extends Model
{
    use HasFactory;

    protected $table = 'categoria_olimpiada';

    protected $fillable = [
        'categoria_id',
        'olimpiada_id',
    ];

    public $timestamps = false;
}
