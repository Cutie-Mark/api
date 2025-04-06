<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Responsable extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'uuid', 
        'nombre_completo', 
        'email', 
        'telefono'
    ];

    // Generar UUID automáticamente al crear el modelo
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // Relación con Listas (usa el UUID como clave foránea)
    public function listas()
    {
        return $this->hasMany(Lista::class, 'id_responsable', 'uuid');
    }
}