<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class BulkInscripcionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ci'                                  => 'required|string',
            'olimpiada_id'                        => 'required|exists:olimpiadas,id',
            'codigo_lista'                        => 'nullable|string|exists:listas,codigo_lista',
            'listaPostulantes'                    => 'required|array|min:1',

            // Validación de CI del postulante: solo dígitos, entre 1 y 10
            'listaPostulantes.*.ci'               => ['required', 'max:11', 'regex:/^(\d{1,8})(-\w{1,3})?$/'],	

            'listaPostulantes.*.nombres'          => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.apellidos'        => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.fecha_nacimiento' => 'required|date_format:d-m-Y',
            'listaPostulantes.*.correo_postulante'=> ['required','email:rfc'],
            //'listaPostulantes.*.email_contacto'   => ['required','email:rfc'],
            'listaPostulantes.*.tipo_contacto_email'     => 'required|integer|in:1,2,3,4',
            'listaPostulantes.*.telefono_contacto'=> ['required','regex:/^[0-9]{7,8}$/'],
            'listaPostulantes.*.tipo_contacto_telefono'=> 'required|integer|in:1,2,3,4',
            'listaPostulantes.*.idDepartamento'   => 'required|exists:departamentos,id',
            'listaPostulantes.*.idProvincia'      => 'required|exists:provincias,id',
            'listaPostulantes.*.idColegio'        => 'required|exists:colegios,id',
            'listaPostulantes.*.idCurso'          => 'required|integer|between:1,12',
            'listaPostulantes.*.inscripciones'    => ['required','array','min:1',
                function($attribute, $value, $fail) {
                    // El atributo tiene forma "listaPostulantes.{i}.inscripciones"
                    $parts = explode('.', $attribute);
                    $index = isset($parts[1]) ? intval($parts[1]) : null;
                    $fila = is_null($index) ? 'desconocida' : $index + 1;
                    $ci = $index !== null
                        ? $this->input("listaPostulantes.$index.ci")
                        : 'desconocido';

                    $olimpiada_id = $this->input('olimpiada_id');
                    if (!$olimpiada_id) {
                        return;
                    }
                    $olimpiada = \App\Models\Olimpiada::find($olimpiada_id);
                    if (!$olimpiada) {
                        return;
                    }

                    if (count($value) > $olimpiada->limite_inscripciones) {
                        // Sólo el mensaje, sin prefijo. failedValidation agregará "error en inscripciones..."
                        $fail("No puedes inscribirte en más de {$olimpiada->limite_inscripciones} niveles de competencia");
                    }
                }
            ],
            'listaPostulantes.*.inscripciones.*.idArea'      => 'required|exists:areas,id',
            'listaPostulantes.*.inscripciones.*.idCategoria' => 'required|exists:categorias,id',
            'listaPostulantes.*.telefono_contacto' => ['required','regex:/^[0-9]{7,8}$/'],

            'listaPostulantes.*.contactos' => 'sometimes|array|min:1',
            'listaPostulantes.*.contactos.*.telefono_contacto' => ['required_without:listaPostulantes.*.contactos.*.email_contacto', 'nullable', 'regex:/^[0-9]{7,8}$/'],
            'listaPostulantes.*.contactos.*.tipo_contacto_telefono' => 'required_with:listaPostulantes.*.contactos.*.telefono_contacto|nullable|integer|in:1,2,3,4',
            'listaPostulantes.*.contactos.*.email_contacto' => ['required_without:listaPostulantes.*.contactos.*.telefono_contacto', 'nullable', 'email:rfc'],
            'listaPostulantes.*.contactos.*.tipo_contacto_email' => 'required_with:listaPostulantes.*.contactos.*.email_contacto|nullable|integer|in:1,2,3,4',
        
        ];
    }

    public function messages()
    {
        return [
            'ci.required'       => 'El CI del responsable es obligatorio',
            'ci.exists'         => 'El CI del responsable no existe en el sistema',

            'olimpiada_id.required' => 'El ID de la olimpiada es obligatorio',
            'olimpiada_id.exists'   => 'La olimpiada especificada no existe',

            'codigo_lista.exists' => 'El código de lista no es válido',

            'listaPostulantes.required' => 'La lista de postulantes es obligatoria',
            'listaPostulantes.array'    => 'La lista de postulantes debe ser un arreglo',
            'listaPostulantes.min'      => 'Debe incluir al menos un postulante',

            // Mensajes para CI del postulante
            'listaPostulantes.*.ci.required'        => 'El CI es obligatorio para todos los postulantes',
            'listaPostulantes.*.ci.regex'           => 'Formato de CI inválido. Debe tener 8 dígitos, seguido opcionalmente de un guión y el complemento (ej: 12345678-1A)',
            'listaPostulantes.*.ci.max'             => 'El CI no puede exceder los 11 caracteres',

            'listaPostulantes.*.nombres.required' => 'El nombre es obligatorio para todos los postulantes',
            'listaPostulantes.*.nombres.regex'    => 'El campo nombres no debe contener números',

            'listaPostulantes.*.apellidos.required' => 'Los apellidos son obligatorios para todos los postulantes',
            'listaPostulantes.*.apellidos.regex'    => 'El campo apellidos no debe contener números',

            'listaPostulantes.*.fecha_nacimiento.required'   => 'La fecha de nacimiento es obligatoria',
            'listaPostulantes.*.fecha_nacimiento.date_format'=> 'La fecha debe estar en formato dd-mm-yyyy',

            'listaPostulantes.*.correo_postulante.required' => 'El correo del postulante es obligatorio',
            'listaPostulantes.*.correo_postulante.email'    => 'El correo del postulante no es válido',

            'listaPostulantes.*.idDepartamento.required' => 'El departamento es obligatorio',
            'listaPostulantes.*.idDepartamento.exists'   => 'El departamento seleccionado no existe',

            'listaPostulantes.*.idProvincia.required' => 'La provincia es obligatoria',
            'listaPostulantes.*.idProvincia.exists'   => 'La provincia seleccionada no existe',

            'listaPostulantes.*.idColegio.required' => 'El colegio es obligatorio',
            'listaPostulantes.*.idColegio.exists'   => 'El colegio seleccionado no existe',

            'listaPostulantes.*.idCurso.required'  => 'El curso es obligatorio',
            'listaPostulantes.*.idCurso.between'   => 'El curso debe estar entre 1ro de primaria y 6to de secundaria',

            'listaPostulantes.*.inscripciones.required' => 'Las inscripciones son obligatorias',
            'listaPostulantes.*.inscripciones.array'    => 'Las inscripciones deben ser un arreglo',
            'listaPostulantes.*.inscripciones.min'      => 'Debe incluir al menos una inscripción',

            'listaPostulantes.*.inscripciones.*.idArea.required'      => 'El área es obligatoria',
            'listaPostulantes.*.inscripciones.*.idArea.exists'        => 'El área seleccionada no existe',

            'listaPostulantes.*.inscripciones.*.idCategoria.required' => 'La categoría es obligatoria',
            'listaPostulantes.*.inscripciones.*.idCategoria.exists'   => 'La categoría seleccionada no existe',
        
            'listaPostulantes.*.telefono_contacto.regex' => 'El teléfono debe tener exactamente 7 u 8 dígitos numéricos',
            'listaPostulantes.*.telefono_contacto.required' => 'El teléfono de contacto es obligatorio',
            ];
    }

    protected function prepareForValidation()
    {
        $listaPostulantes = $this->input('listaPostulantes', []);
        if (!is_array($listaPostulantes)) {
            $this->merge(['listaPostulantes' => []]);
            return;
        }

        foreach ($listaPostulantes as $i => $post) {
            // Normalizar fecha de nacimiento a formato d-m-Y
            $rawFecha = $post['fecha_nacimiento'] ?? null;
            
            if (is_string($rawFecha)) {
                try {
                    // Si la fecha viene en formato JavaScript
                    if (strpos($rawFecha, 'GMT') !== false) {
                        $fecha = new \DateTime($rawFecha);
                        $this->merge([
                            "listaPostulantes.$i.fecha_nacimiento" => $fecha->format('d-m-Y')
                        ]);
                    }
                    // Si viene en formato YYYY-MM-DD
                    else if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $rawFecha)) {
                        $fecha = \Carbon\Carbon::createFromFormat('Y-m-d', $rawFecha);
                        $this->merge([
                            "listaPostulantes.$i.fecha_nacimiento" => $fecha->format('d-m-Y')
                        ]);
                    }
                    // Si ya viene en formato d-m-Y lo dejamos así
                } catch (\Exception $e) {
                    // Si hay error al parsear, dejamos el valor original y la validación fallará
                }
            }

            // Asegurar que inscripciones sea un array
            if (!isset($post['inscripciones']) || !is_array($post['inscripciones'])) {
                $this->merge([
                    "listaPostulantes.$i.inscripciones" => []
                ]);
            }

            // Asegurar que todos los campos requeridos existan
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

    /**
     * Configure the validator instance with custom logic
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ci = $this->input('ci');
            
            // Validar la existencia del responsable con CI encriptado
            $responsableExiste = false;
            $allResponsables = \App\Models\Responsable::all();
            foreach ($allResponsables as $resp) {
                if ($resp->ci === $ci) {
                    $responsableExiste = true;
                    break;
                }
            }
            
            if (!$responsableExiste) {
                $validator->errors()->add('ci', 'El CI del responsable no está registrado en el sistema.');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        $formatted = [];
        $allMessages = $validator->errors()->getMessages();

        foreach ($allMessages as $attributeKey => $messages) {
            // Si es validación de campo superior (ci, olimpiada_id, codigo_lista), devolver solo el mensaje
            if (in_array($attributeKey, ['ci', 'olimpiada_id', 'codigo_lista'])) {
                foreach ($messages as $msg) {
                    $formatted[] = $msg;
                }
                continue;
            }

            // Para validaciones de listaPostulantes.*, extraer índice y CI
            $parts = explode('.', $attributeKey);
            $index = isset($parts[1]) ? intval($parts[1]) : null;
            $fila = is_null($index) ? 'desconocida' : $index + 1;
            $ci = $index !== null
                ? $this->input("listaPostulantes.$index.ci")
                : 'desconocido';

            foreach ($messages as $msg) {
                $formatted[] = "error en inscripciones de la fila {$fila} del estudiante con CI {$ci}: {$msg}";
            }
        }

        throw new ValidationException($validator, response()->json([
            'errores' => $formatted
        ], 422));
    }
}
