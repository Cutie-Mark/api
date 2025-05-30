<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colegio extends Model
{
    use HasFactory;

    protected $table = 'colegios';

    protected $fillable = ['nombre','created_at', 'updated_at'];

    protected $hidden = ['created_at', 'updated_at'];

    public function inscripciones() {
        return $this->hasMany(Inscripcion::class, 'colegio_id');
    }

};