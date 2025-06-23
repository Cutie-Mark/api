<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CrearOrdenPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o ajusta según tus políticas de autorización
    }

    public function rules(): array
    {
        return [
            'codigo_lista'       => 'required|string|exists:listas,codigo_lista',
            'nombre_responsable' => ['required','string','max:60','regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/'],
            'emitido_por'        => 'required|string|max:60',
            'nitci'              => ['required','regex:/^[0-9]{1,10}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_lista.required'       => 'El código de lista es obligatorio.',
            'codigo_lista.exists'         => 'Código de lista invalido.',
            'nombre_responsable.regex'    => 'El nombre de responsable solo debe contener caracteres alfabéticos.',
            'nombre_responsable.max'      => 'El nombre de responsable no debe exceder 60 caracteres.',
            'nombre_responsable.required' => 'El campo nombre de responsable es obligatorio.',
            'emitido_por.required'        => 'El campo emitido_por es obligatorio.',
            'emitido_por.max'             => 'El campo emitido_por no debe exceder 60 caracteres.',
            'nitci.required'              => 'El campo nitci es obligatorio.',
            'nitci.regex'                 => 'El nitci debe contener solo dígitos y como máximo 10 caracteres.',
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
