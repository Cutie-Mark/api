<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lista extends Model
{
    use HasFactory;

    protected $fillable = ['responsable_id','nombre_lista', 'codigo_lista'];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($lista) {
            $lista->codigo_lista = strtoupper(bin2hex(random_bytes(8))); // Genera 16 caracteres alfanuméricos
        });
    }

    public function responsable()
    {
        return $this->belongsTo(Responsable::class);
    }
}