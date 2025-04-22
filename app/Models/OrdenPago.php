<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenPago extends Model
{
    use HasFactory;

    protected $table = 'ordenes_pagos';

    protected $fillable = [
        'lista_id',
        'monto',
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
            Inscripcion::class, 'orden_pago_id');
    }
    public function lista()
    {
        return $this->belongsTo(Lista::class);
    }

}
