<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;

    protected $table = 'links';

    protected $fillable = [
        'enlace_archivo',
        'peso_archivo',
    ];
}
