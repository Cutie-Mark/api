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
    
    // Mutators para encriptar datos al guardar
    public function setNombreCompletoAttribute($value)
    {
        $this->attributes['nombre_completo'] = Crypt::encryptString($value);
    }
    
    public function setCiAttribute($value)
    {
        $this->attributes['ci'] = Crypt::encryptString($value);
    }
    
    // Accessors para desencriptar datos al recuperar
    public function getNombreCompletoAttribute($value)
    {
        return !empty($value) ? Crypt::decryptString($value) : null;
    }
    
    public function getCiAttribute($value)
    {
        return !empty($value) ? Crypt::decryptString($value) : null;
    }

    public function listas()
    {
        return $this->hasMany(Lista::class, 'responsable_id');
    }

    public function inscripciones()
    {
        return $this ->hasMany(Inscripcion::class, 'responsable_id');
    }
}