<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';
    public $timestamps = false;

    protected $fillable = [
        'postulante_id',
        'email',
        'tipo_contacto_email',
        'telefono',
        'tipo_contacto_telefono',
        'estado',
        'lista_id',
        'area_id',
        'categoria_id',
        'colegio_id',
        'olimpiada_id',
        'orden_pago_id'
    ];

    // Relaciones
    public function postulante()
    {
        return $this->belongsTo(Postulante::class, 'postulante_id');
    }

    public function lista()
    {
        return $this->belongsTo(Lista::class, 'lista_id', 'codigo_lista');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function ordenPago() 
    {
        return $this->belongsTo(OrdenPago::class, 'orden_pago_id');
    }

    public function colegio()
    {
        return $this->belongsTo(Colegio::class);
    }

    public function olimpiada()
    {
        return $this->belongsTo(Olimpiada::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

}