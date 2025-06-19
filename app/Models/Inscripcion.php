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
        'responsable_id',
        'nivel_competencia_id',
        'colegio_id',
        'orden_pago_id',
        'lista_id',
        'email',
        'tipo_contacto_email',
        'telefono',
        'tipo_contacto_telefono',
        'estado'
    ];

    protected $with = ['nivelCompetencia.olimpiada'];
    
    public static $tipoContactoMap = [
        1 => 'padre/madre',
        2 => 'profesor',
        3 => 'estudiante',
        4 => 'responsable',
    ];

    // Relaciones
    public function postulante()
    {
        return $this->belongsTo(Postulante::class, 'postulante_id');
    }

    public function lista()
    {
        return $this->belongsTo(Lista::class, 'lista_id');
    }

    public function ordenPago() 
    {
        return $this->belongsTo(OrdenPago::class, 'orden_pago_id');
    }

    public function colegio()
    {
        return $this->belongsTo(Colegio::class, 'colegio_id');
    }

    public function nivelCompetencia()
    {
        return $this->belongsTo(NivelCompetencia::class, 'nivel_competencia_id')->with('olimpiada');
    }

    public function responsable()
    {
        return $this->belongsTo(Responsable::class, 'responsable_id');
    }

    public function olimpiada()
    {
        return $this->hasOneThrough(
            \App\Models\Olimpiada::class,          // Modelo destino
            \App\Models\NivelCompetencia::class,   // Modelo intermedio
            'id',        // PK de niveles_competencia
            'id',        // PK de olimpiadas
            'nivel_competencia_id', // FK local en inscripciones
            'olimpiada_id'         // FK local en niveles_competencia
        );
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

}