<?php

namespace App\Services;

use App\Models\Olimpiada;

class OlimpiadaService
{
    // Método para verificar si hay olimpiadas en curso
    public function hayOlimpiadaEnCurso()
    {
        $hoy = now();  // Obtén la fecha y hora actual

        // Verifica si hay una olimpiada cuyo rango de fechas incluya hoy
        return Olimpiada::where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->exists();
    }
}
