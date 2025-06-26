<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

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
