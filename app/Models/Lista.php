<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lista extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_lista',
        'id_responsable', 
        'estado'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lista) {
            $lista->codigo_lista = Str::uuid(); 
            $lista->fecha_creacion = now();
        });
    }

    // Relación con Responsable (vía UUID)
    public function responsable()
    {
        return $this->belongsTo(
            Responsable::class, 'id_responsable','uuid');
    }

    // Relación con Inscripciones
    public function inscripciones()
    {
        return $this->hasMany(
            Inscripcion::class, 'lista_id', 'codigo_lista');
    }
}