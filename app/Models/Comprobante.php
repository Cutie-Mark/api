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
        'n_orden',
        'codigo_lista',
        'fecha_pago',
        'precio_unitario',
        'cantidad_inscripciones',
        'monto',
        'estado',
        'responsable_pago',
        'nitci'
    ];

    protected $casts = [
        'fecha_pago' => 'datetime'
    ];

    public function ordenPago()
    {
        return $this->belongsTo(OrdenPago::class, 'orden_pago_id');
    }
}
