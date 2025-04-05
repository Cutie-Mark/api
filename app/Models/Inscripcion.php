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
        'fecha_inscripcion',
        'postulante_id',       
        'categoria_id',        
        'email_contacto',
        'tipo_contacto_email',
        'telefono_contacto',
        'tipo_contacto_telefono',
        'lista_id',            
        'orden_pago_id',       
        'colegio_id',          
        'olimpiada_id',       
        'area_id',             
        'estado',
    ];

    protected $casts = [
        'fecha_inscripcion' => 'datetime',
    ];

    // Relaciones
    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }

    public function lista()
    {
        return $this->belongsTo(Lista::class);
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

    // Accesor para el responsable (no es una relación Eloquent)
    public function getResponsableAttribute()
    {
        return $this->lista->responsable;
    }
    /*
    public static function boot()
    {
        parent::boot();

        static::creating(function ($inscripcion) {
            $lista = $inscripcion->lista;
            if ($lista->inscripciones()->exists() && $lista->inscripciones()->first()->area_id != $inscripcion->area_id) {
                throw new \Exception("Todas las inscripciones de una lista deben ser del mismo área");
            }
        });
    }*/

}