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
        'responsable_id', 
        'olimpiada_id',
        'estado'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lista) {
            $lista->codigo_lista = self::generarCodigoUnico(); 
            //$lista->fecha_creacion = now();
        });
    }

    // Generar código único de 6 caracteres
    protected static function generarCodigoUnico()
    {
        do {
            $codigo = Str::upper(Str::random(6)); // Código alfanumérico en mayúsculas
        } while (self::where('codigo_lista', $codigo)->exists());

        return $codigo;
    }

    // Relación con Responsable 
    public function responsable()
    {
        return $this->belongsTo(
            Responsable::class, 'responsable_id');
    }

    // Relación con Inscripciones
    public function inscripciones()
    {
        return $this->hasMany(
            Inscripcion::class, 'lista_id', 'id');
    }
    // Relación con Olimpiada
    public function olimpiada()
    {
        return $this->belongsTo(Olimpiada::class, 'olimpiada_id');
    }


}