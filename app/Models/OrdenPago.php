<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenPago extends Model
{
    use HasFactory;

    protected $table = 'ordenes_pagos';

    protected $fillable = [
        'fecha_emision',
        'monto',
        'codigo_lista',
        'estado',
        'cantidad_inscripciones', 
        'senior',
        'emitido_por',
        'nitci'
    ];

    // Relación con Inscripciones
    public function inscripciones()
    {
        return $this->hasMany(
            Inscripcion::class, 'orden_pago_id', 'id');
    }

}
