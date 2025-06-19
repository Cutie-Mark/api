<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Colegio;
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
    
    // Mutators para encriptar datos al guardar
    public function setNombresAttribute($value)
    {
        $this->attributes['nombres'] = Crypt::encryptString($value);
    }
    
    public function setApellidosAttribute($value)
    {
        $this->attributes['apellidos'] = Crypt::encryptString($value);
    }
    
    public function setCiAttribute($value)
    {
        $this->attributes['ci'] = Crypt::encryptString($value);
    }
    
    // Accessors para desencriptar datos al recuperar
    public function getNombresAttribute($value)
    {
        return !empty($value) ? Crypt::decryptString($value) : null;
    }
    
    public function getApellidosAttribute($value)
    {
        return !empty($value) ? Crypt::decryptString($value) : null;
    }
    
    public function getCiAttribute($value)
    {
        return !empty($value) ? Crypt::decryptString($value) : null;
    }
    
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
            'postulante_id', // Foreign key on inscripciones table
            'id', // Foreign key on colegios table
            'id', // Local key on postulantes table
            'colegio_id' // Local key on inscripciones table
        );
    }

    // Añade esta relación al modelo
    public function contactos()
    {
        return $this->hasMany(Contacto::class);
    }
    
}