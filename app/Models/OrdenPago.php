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
        'n_orden',
        'monto',
        'estado',
        'cantidad_inscripciones',
        'nombre_responsable',
        'emitido_por',
        'nitci',
        'unidad',
        'concepto',
        'fecha_emision',
        'fecha_pago'
    ];

    protected $appends = [
        'codigo_lista',
        'precio_unitario',
        'importe',
        'cantidad',
        'niveles_competencia',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_pago' => 'datetime',
    ];

    // Relaciones
    public function lista()
    {
        return $this->belongsTo(Lista::class);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'orden_pago_id');
    }

    // Accessors
    public function getCodigoListaAttribute(): string
    {
        return $this->lista->codigo_lista;
    }

    public function getPrecioUnitarioAttribute(): float
    {
        return 15.00;
    }

    public function getImporteAttribute(): float
    {
        return (float) $this->monto;
    }

    public function getCantidadAttribute(): int
    {
        return $this->cantidad_inscripciones;
    }

    public function getUnidadAttribute(): string
    {
        return 'Inscripción';
    }

    public function getConceptoAttribute(): string
{
    $codigo = $this->lista->codigo_lista;
    return "Inscripcion Olimpiada San Simon acorde a la lista {$codigo}";
}

    public function getNivelesCompetenciaAttribute(): array
    {
        return $this->lista->inscripciones->map(function($ins) {
            $nc = $ins->nivelCompetencia;
            return $nc->area->nombre . ' - ' . $nc->categoria->nombre;
        })->unique()->values()->all();
    }
}