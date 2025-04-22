<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    // Define la tabla asociada al modelo (opcional, si se sigue la convención de nombres, no es necesario)
    protected $table = 'departamentos';

    // Definir los campos que son asignables en masa (mass assignment)
    protected $fillable = [
        'nombre', 
        'abreviatura'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // Relación con Provincias
    public function provincias()
    {
        return $this->hasMany(Provincia::class);
    }
}
