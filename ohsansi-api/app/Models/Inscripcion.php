<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';

    protected $fillable = [
        'fecha_inscripcion',
        'postulante_id',
        'categoria_id',
        'email_contacto',
        'tipo_contacto_email',
        'telefono_contacto',
        'tipo_contacto_telefono',
        'responsable_id',
        'lista_id',
    //    'id_orden_pago',
        'id_colegio',
        'id_olimpiada',
        'id_area'
    ];

    protected $casts = [
        'fecha_inscripcion' => 'datetime',
    ];

    // Relaciones
    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function responsable()
    {
        return $this->belongsTo(Responsable::class);
    }

    public function lista()
    {
        return $this->belongsTo(Lista::class);
    }

    //public function ordenPago()
    //{
      //  return $this->belongsTo(OrdenPago::class, 'id_orden_pago');
    //}

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
