<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Crypt;

class Responsable extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'nombre_completo',
        'ci',
        'email',
        'telefono'
    ];

    /**
     * Almacenar “nombre_completo” cifrado
     */
    public function setNombreCompletoAttribute($value)
    {
        $formateado = ucwords(strtolower($value));
        $this->attributes['nombre_completo'] = Crypt::encryptString($formateado);
    }

    /**
     * Obtener “nombre_completo” desencriptado
     */
    public function getNombreCompletoAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Almacenar “ci” cifrado
     */
    public function setCiAttribute($value)
    {
        $this->attributes['ci'] = Crypt::encryptString($value);
    }

    /**
     * Obtener “ci” desencriptado
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

    public function listas()
    {
        return $this->hasMany(Lista::class, 'responsable_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'responsable_id');
    }
}
