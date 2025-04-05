<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lista extends Model
{
    use HasFactory;

    protected $fillable = ['responsable_id']; 
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lista) {
            $lista->codigo_lista = self::generarCodigoUnico();
            //$lista->fecha_creacion = now(); 
        });
    }

    //Generar código 
    private static function generarCodigoUnico()
    {
        do {
            $codigo = 'LISTA-'.Str::upper(Str::random(8)); // Ej: "A1B2C3D4E5F6G7H8"
        } while (self::where('codigo_lista', $codigo)->exists());

        return $codigo;
    }

    // Relaciones
    public function responsable()
    {
        return $this->belongsTo(Responsable::class, 'responsable_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'lista_id');
    }
}