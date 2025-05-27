<?php

namespace App\Services;

class TextoService
{
    public function normalizar(string $texto): string
    {
        $mapa = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Ñ' => 'N', 'ñ' => 'n' 
        ];

        $texto = strtr($texto, $mapa);

        return $texto;
    }
}
