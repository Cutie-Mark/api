<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Postulante extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'provincia_id',
        'email',
        'ci',
        'curso'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * ENCRIPTAR “nombres” al asignarlo
     */
    public function setNombresAttribute($value)
    {
        $this->attributes['nombres'] = Crypt::encryptString($value);
    }

    /**
     * DESENCRIPTAR “nombres” al leerlo
     */
    public function getNombresAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * ENCRIPTAR “apellidos” al asignarlo
     */
    public function setApellidosAttribute($value)
    {
        $this->attributes['apellidos'] = Crypt::encryptString($value);
    }

    /**
     * DESENCRIPTAR “apellidos” al leerlo
     */
    public function getApellidosAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * ENCRIPTAR “ci” al asignarlo
     */
    public function setCiAttribute($value)
    {
        $this->attributes['ci'] = Crypt::encryptString($value);
    }

    /**
     * DESENCRIPTAR “ci” al leerlo
     */
    public function getCiAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**************** Relaciones ****************/

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'postulante_id');
    }

    public function colegio()
    {
        return $this->hasOneThrough(
            Colegio::class,
            Inscripcion::class,
            'postulante_id', // Foreign key on inscripciones
            'id',            // Foreign key on colegios
            'id',            // Local key on postulantes
            'colegio_id'     // Local key on inscripciones
        );
    }

    public function contactos()
    {
        return $this->hasMany(Contacto::class);
    }
}
