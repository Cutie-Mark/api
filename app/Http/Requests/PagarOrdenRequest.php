<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class PagarOrdenRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'n_orden_pago' => 'required|string',
            'codigo_lista' => 'required|string|exists:listas,codigo_lista',
            'fecha'        => 'required|date|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'n_orden_pago.required' => 'El número de orden es obligatorio.',
            'n_orden_pago.string'   => 'El número de orden debe ser texto.',
            'codigo_lista.required' => 'El código de lista es obligatorio.',
            'codigo_lista.string'   => 'El código de lista debe ser texto.',
            'codigo_lista.exists'   => 'El código de lista proporcionado no existe en el sistema.',
            'fecha.required'        => 'La fecha de pago es obligatoria.',
            'fecha.date'            => 'El formato de fecha es inválido.',
            'fecha.date_format'     => 'La fecha debe tener el formato YYYY-MM-DD (ej: 2025-06-15).',
        ];
    }
    
    /**
     * Sobrescribimos el método para personalizar la respuesta de error
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'error' => $validator->errors()->first()
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
