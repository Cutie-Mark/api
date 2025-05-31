<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contacto extends Model
{
    protected $table = 'contactos';
    public $timestamps = true;

    protected $fillable = [
        'postulante_id',
        'telefono',
        'tipo_contacto_telefono',
        'email',
        'tipo_contacto_email'
    ];

    public static $tipoContactoMap = [
        1 => 'padre/madre',
        2 => 'profesor',
        3 => 'estudiante',
        4 => 'responsable',
    ];

    public function setTipoContactoEmailAttribute($value)
    {
        $this->attributes['tipo_contacto_email'] = self::$tipoContactoMap[$value] ?? $value;
    }

    public function setTipoContactoTelefonoAttribute($value)
    {
        $this->attributes['tipo_contacto_telefono'] = self::$tipoContactoMap[$value] ?? $value;
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class);
    }
}