<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    use HasFactory;

    protected $table = 'comprobantes';

    protected $primaryKey = 'id_comprobante';

    protected $fillable = [
        'orden_pago_id',
        'codigo',
        'nombre_pagador',
        'url_comprobante',
        'fecha_pago',
        'descripcion',
        'ci_nit'
    ];

    public function ordenPago()
    {
        return $this->belongsTo(OrdenPago::class, 'orden_pago_id');
    }
}
