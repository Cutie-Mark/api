<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInscripcionRequest extends FormRequest
{
    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'error' => $validator->errors()->first()
        ], 422));
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'nombres'                => ['required','string','max:255','regex:/^[^\d]+$/'],
            'apellidos'              => ['required','string','max:255','regex:/^[^\d]+$/'],
            'ci'                     => ['required','string','max:10','regex:/^\d{1,10}$/'],
            'fecha_nacimiento'       => 'required|date_format:d-m-Y',
            'correo_postulante'      => ['required','email:rfc'],
            'curso'                  => 'required|integer|between:1,12',
            'departamento'           => 'required|exists:departamentos,id',
            'provincia'              => 'required|exists:provincias,id',
            'niveles_competencia'    => ['required', 'array', 'min:1', function($attribute, $value, $fail) {
                $olimpiada_id = request('olimpiada_id');
                if (!$olimpiada_id) {
                    return;
                }
                $olimpiada = \App\Models\Olimpiada::find($olimpiada_id);
                if (!$olimpiada) {
                    return;
                }
                
                if (count($value) > $olimpiada->limite_inscripciones) {
                    $fail("No puedes inscribirte en más de {$olimpiada->limite_inscripciones} niveles de competencia");
                }
            }],
            'niveles_competencia.*.id_area'        => 'required|exists:areas,id',
            'niveles_competencia.*.id_cat'         => 'required|exists:categorias,id',
            'email_contacto'         => ['required','email:rfc'],
            'tipo_contacto_email'    => 'required|integer|in:1,2,3',
            'telefono_contacto'      => ['required', 'regex:/^[0-9]{7,8}$/'],
            'tipo_contacto_telefono' => 'required|integer|in:1,2,3',
            'colegio'               => 'required|exists:colegios,id',
            'codigo_lista'          => 'required|string|exists:listas,codigo_lista'
        ];
    }    public function messages()
    {
        return [
            'required'                => 'El campo :attribute es obligatorio',
            'exists'                 => 'El valor seleccionado en :attribute no es válido',
            'niveles_competencia.required' => 'Debe seleccionar al menos un área y categoría para la inscripción',
            'niveles_competencia.min' => 'Debe seleccionar al menos un área y categoría para la inscripción',
            'niveles_competencia.max'=> 'No puedes inscribirte en más de :max niveles de competencia',
            'niveles_competencia.*.id_area.required' => 'Debe seleccionar un área para cada nivel de competencia',
            'niveles_competencia.*.id_area.exists' => 'El área seleccionada no es válida o no está disponible para inscripción',
            'niveles_competencia.*.id_cat.required' => 'Debe seleccionar una categoría para cada nivel de competencia',
            'niveles_competencia.*.id_cat.exists' => 'La categoría seleccionada no es válida o no está disponible para inscripción',
            'between'                => 'El curso debe estar entre 1ro de primaria y 6to de secundaria',
            'nombres.regex'          => 'El campo nombres no debe contener numeros',
            'apellidos.regex'        => 'El campo apellidos no debe contener numeros',
            'ci.regex'               => 'CI no debe contener letras',
            'ci.max'                 => 'CI no debe tener más de 10 dígitos',
            'correo_postulante.email' => 'Tipo de correo inválido en campo correo postulante',
            'email_contacto.email'    => 'Tipo de correo inválido en campo email contacto',
            'tipo_contacto_email.in'  => 'Tipo de contacto inválido en email contacto',
            'tipo_contacto_telefono.in' => 'Tipo de contacto inválido en telefono contacto',
            'telefono_contacto.regex' => 'Teléfono incorrecto, debe tener entre 7 y 8 dígitos',
            'colegio.exists'          => 'Colegio no encontrado',
            'codigo_lista.exists'     => 'Codigo de lista invalido',
            'codigo_lista.required'   => 'Codigo de lista vacio'
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('codigo_lista') && $this->input('codigo_lista') === '') {
            $this->merge(['codigo_lista' => null]);
        }
    }
}
