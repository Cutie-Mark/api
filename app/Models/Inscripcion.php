<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';
    public $timestamps = true;

    protected $fillable = [
        'postulante_id',
        'email',
        'tipo_contacto_email',
        'telefono',
        'tipo_contacto_telefono',
        'estado',
        'lista_id',
        //'area_id',
        //'categoria_id',
        'nivel_competencia_id',
        'colegio_id',
        'olimpiada_id',
        'orden_pago_id'
    ];

    public static $tipoContactoMap = [
        1 => 'padre/madre',
        2 => 'profesor',
        3 => 'estudiante'
    ];

    public function setTipoContactoEmailAttribute($value)
    {
        $this->attributes['tipo_contacto_email'] = self::$tipoContactoMap[$value] ?? $value;
    }

    public function setTipoContactoTelefonoAttribute($value)
    {
        $this->attributes['tipo_contacto_telefono'] = self::$tipoContactoMap[$value] ?? $value;
    }

    // Relaciones
    public function postulante()
    {
        return $this->belongsTo(Postulante::class, 'postulante_id');
    }

    public function lista()
    {
        return $this->belongsTo(Lista::class, 'lista_id');
    }

   /* public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }*/

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
/*
    public function area()
    {
        return $this->belongsTo(Area::class);
    }*/

    public function nivel_competencia()
    {
        return $this->belongsTo(NivelCompetencia::class);
    }

    public function responsable()
    {
        return $this->belongsTo(
            Responsable::class, 'responsable_id');
    }

}