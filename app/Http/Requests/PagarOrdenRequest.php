<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PagarOrdenRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'n_orden_pago' => 'required|string|exists:ordenes_pagos,n_orden',
            'codigo_lista' => 'required|string|exists:listas,codigo_lista',
            'fecha'        => 'required|date',
        ];
    }

    public function messages()
    {
        return [
            'n_orden_pago.required' => 'El número de orden es obligatorio.',
            'n_orden_pago.exists'   => 'Número de orden inválido.',
            'codigo_lista.required' => 'El código de lista es obligatorio.',
            'codigo_lista.exists'   => 'Código de lista inválido.',
            'fecha.required'        => 'La fecha de pago es obligatoria.',
            'fecha.date'           => 'El formato de fecha es inválido.',
        ];
    }
}
