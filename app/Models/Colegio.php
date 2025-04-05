<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colegio extends Model
{
    use HasFactory;

    protected $table = 'colegios';

    protected $fillable = ['nombre'];

    public function inscripciones() {
        return $this->hasMany(Inscripcion::class, 'id_colegio');
    }

};