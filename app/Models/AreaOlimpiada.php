<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaOlimpiada extends Model
{
    use HasFactory;

    protected $table = 'area_olimpiada';

    protected $fillable = [
        'area_id',
        'olimpiada_id',
    ];

    public $timestamps = false;
}
