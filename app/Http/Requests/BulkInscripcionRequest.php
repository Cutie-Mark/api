<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class BulkInscripcionRequest extends FormRequest
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
            'ci'                                 => 'required|string|exists:responsables,ci',
            'olimpiada_id'                       => 'required|exists:olimpiadas,id',
            'codigo_lista'                       => 'nullable|string|exists:listas,codigo_lista',
            'listaPostulantes'                   => 'required|array|min:1',
            'listaPostulantes.*.nombres'         => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.apellidos'       => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.ci'              => ['required','string','max:10','regex:/^\d{1,10}$/'],
            'listaPostulantes.*.fecha_nacimiento'=> 'required|date_format:d-m-Y',
            'listaPostulantes.*.correo_postulante'=> ['required','email:rfc'],
            'listaPostulantes.*.email_contacto'  => ['required','email:rfc'],
            'listaPostulantes.*.tipo_contacto_email' => 'required|integer|in:1,2,3',
            'listaPostulantes.*.telefono_contacto'=> ['required', 'regex:/^[0-9]{7,8}$/'],
            'listaPostulantes.*.tipo_contacto_telefono' => 'required|integer|in:1,2,3',
            'listaPostulantes.*.idDepartamento'  => 'required|exists:departamentos,id',
            'listaPostulantes.*.idProvincia'     => 'required|exists:provincias,id',
            'listaPostulantes.*.idColegio'       => 'required|exists:colegios,id',
            'listaPostulantes.*.idCurso'         => 'required|integer|between:1,12',
            'listaPostulantes.*.inscripciones'   => ['required', 'array', 'min:1', function($attribute, $value, $fail) {
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
            'listaPostulantes.*.inscripciones.*.idArea'      => 'required|exists:areas,id',
            'listaPostulantes.*.inscripciones.*.idCategoria' => 'required|exists:categorias,id',
        ];
    }    public function messages()
    {
        return [
            'ci.required' => 'El CI del responsable es obligatorio',
            'ci.exists' => 'El CI del responsable no existe en el sistema',
            'olimpiada_id.required' => 'El ID de la olimpiada es obligatorio',
            'olimpiada_id.exists' => 'La olimpiada especificada no existe',
            'listaPostulantes.required' => 'La lista de postulantes es obligatoria',
            'listaPostulantes.array' => 'La lista de postulantes debe ser un arreglo',
            'listaPostulantes.min' => 'Debe incluir al menos un postulante',
            'listaPostulantes.*.nombres.required' => 'El nombre es obligatorio para todos los postulantes',
            'listaPostulantes.*.nombres.regex' => 'El campo nombres no debe contener números',
            'listaPostulantes.*.apellidos.required' => 'Los apellidos son obligatorios para todos los postulantes',
            'listaPostulantes.*.apellidos.regex' => 'El campo apellidos no debe contener números',
            'listaPostulantes.*.ci.required' => 'El CI es obligatorio para todos los postulantes',
            'listaPostulantes.*.ci.regex' => 'CI no debe contener letras',
            'listaPostulantes.*.ci.max' => 'CI no debe tener más de 10 dígitos',
            'listaPostulantes.*.fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria',
            'listaPostulantes.*.fecha_nacimiento.date_format' => 'La fecha debe estar en formato dd-mm-yyyy',
            'listaPostulantes.*.correo_postulante.required' => 'El correo del postulante es obligatorio',
            'listaPostulantes.*.correo_postulante.email' => 'El correo del postulante no es válido',
            'listaPostulantes.*.email_contacto.required' => 'El correo de contacto es obligatorio',
            'listaPostulantes.*.email_contacto.email' => 'El correo de contacto no es válido',
            'listaPostulantes.*.tipo_contacto_email.required' => 'El tipo de contacto email es obligatorio',
            'listaPostulantes.*.tipo_contacto_email.in' => 'El tipo de contacto email debe ser 1, 2 o 3',
            'listaPostulantes.*.telefono_contacto.required' => 'El teléfono de contacto es obligatorio',
            'listaPostulantes.*.telefono_contacto.regex' => 'El teléfono debe tener entre 7 y 8 dígitos',
            'listaPostulantes.*.tipo_contacto_telefono.required' => 'El tipo de contacto teléfono es obligatorio',
            'listaPostulantes.*.tipo_contacto_telefono.in' => 'El tipo de contacto teléfono debe ser 1, 2 o 3',
            'listaPostulantes.*.idDepartamento.required' => 'El departamento es obligatorio',
            'listaPostulantes.*.idDepartamento.exists' => 'El departamento seleccionado no existe',
            'listaPostulantes.*.idProvincia.required' => 'La provincia es obligatoria',
            'listaPostulantes.*.idProvincia.exists' => 'La provincia seleccionada no existe',
            'listaPostulantes.*.idColegio.required' => 'El colegio es obligatorio',
            'listaPostulantes.*.idColegio.exists' => 'El colegio seleccionado no existe',
            'listaPostulantes.*.idCurso.required' => 'El curso es obligatorio',
            'listaPostulantes.*.idCurso.between' => 'El curso debe estar entre 1ro de primaria y 6to de secundaria',
            'listaPostulantes.*.inscripciones.required' => 'Las inscripciones son obligatorias',
            'listaPostulantes.*.inscripciones.array' => 'Las inscripciones deben ser un arreglo',
            'listaPostulantes.*.inscripciones.min' => 'Debe incluir al menos una inscripción',
            'listaPostulantes.*.inscripciones.*.idArea.required' => 'El área es obligatoria',
            'listaPostulantes.*.inscripciones.*.idArea.exists' => 'El área seleccionada no existe',
            'listaPostulantes.*.inscripciones.*.idCategoria.required' => 'La categoría es obligatoria',
            'listaPostulantes.*.inscripciones.*.idCategoria.exists' => 'La categoría seleccionada no existe',
            'codigo_lista.exists' => 'El código de lista no es válido'
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('codigo_lista') && $this->input('codigo_lista') === '') {
            $this->merge(['codigo_lista' => null]);
        }

        // Asegurarse de que listaPostulantes sea un array
        $listaPostulantes = $this->input('listaPostulantes', []);
        if (!is_array($listaPostulantes)) {
            $this->merge(['listaPostulantes' => []]);
            return;
        }

        // Normalizar datos para cada postulante
        foreach ($listaPostulantes as $i => $post) {
            // Normalizar fechas de nacimiento
            $rawFecha = $post['fecha_nacimiento'] ?? null;

            // Si el string coincide con "YYYY-MM-DD"
            if (is_string($rawFecha) && preg_match('#^\d{4}-\d{2}-\d{2}$#', $rawFecha)) {
                $carbon = Carbon::createFromFormat('Y-m-d', $rawFecha);
                // Sobrescribimos ese campo para que quede en "DD-MM-YYYY"
                $this->merge([
                    "listaPostulantes.$i.fecha_nacimiento" => $carbon->format('d-m-Y')
                ]);
            }

            // Asegurar que las inscripciones sean un array
            if (!isset($post['inscripciones']) || !is_array($post['inscripciones'])) {
                $this->merge([
                    "listaPostulantes.$i.inscripciones" => []
                ]);
            }

            // Asegurar que los campos requeridos existan
            $requiredFields = [
                'nombres', 'apellidos', 'ci', 'fecha_nacimiento',
                'correo_postulante', 'email_contacto', 'tipo_contacto_email',
                'telefono_contacto', 'tipo_contacto_telefono',
                'idDepartamento', 'idProvincia', 'idColegio', 'idCurso'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($post[$field])) {
                    $this->merge([
                        "listaPostulantes.$i.$field" => null
                    ]);
                }
            }
        }
    }
}
