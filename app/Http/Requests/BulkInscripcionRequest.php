<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use DateTime;

class BulkInscripcionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Ya no usamos "exists:responsables,ci" aquí:
            'ci'                 => ['required', 'string'],
            'olimpiada_id'       => ['required', 'exists:olimpiadas,id'],
            'codigo_lista'       => ['nullable', 'string', 'exists:listas,codigo_lista'],
            'listaPostulantes'   => ['required', 'array', 'min:1'],

            'listaPostulantes.*.ci'               => ['required', 'max:11', 'regex:/^(\d{1,8})(-\w{1,3})?$/'],
            'listaPostulantes.*.nombres'          => ['required', 'string', 'max:255', 'regex:/^[^\d]+$/'],
            'listaPostulantes.*.apellidos'        => ['required', 'string', 'max:255', 'regex:/^[^\d]+$/'],
            'listaPostulantes.*.fecha_nacimiento' => ['required', 'date_format:d-m-Y'],
            'listaPostulantes.*.correo_postulante'=> ['required', 'email:rfc'],
            'listaPostulantes.*.tipo_contacto_email'     => ['required','integer','in:1,2,3,4'],
            'listaPostulantes.*.telefono_contacto'       => ['required','regex:/^[0-9]{7,8}$/'],
            'listaPostulantes.*.tipo_contacto_telefono'  => ['required','integer','in:1,2,3,4'],
            'listaPostulantes.*.idDepartamento'   => ['required','exists:departamentos,id'],
            'listaPostulantes.*.idProvincia'      => ['required','exists:provincias,id'],
            'listaPostulantes.*.idColegio'        => ['required','exists:colegios,id'],
            'listaPostulantes.*.idCurso'          => ['required','integer','between:1,12'],

            'listaPostulantes.*.inscripciones' => [
                'required','array','min:1',
                function ($attribute, $value, $fail) {
                    $parts = explode('.', $attribute);
                    $index = isset($parts[1]) ? intval($parts[1]) : null;
                    $fila = is_null($index) ? 'desconocida' : $index + 1;
                    $ci = $index !== null ? $this->input("listaPostulantes.$index.ci") : 'desconocido';

                    $olimpiada_id = $this->input('olimpiada_id');
                    if (!$olimpiada_id) return;

                    $olimpiada = \App\Models\Olimpiada::find($olimpiada_id);
                    if (!$olimpiada) return;

                    if (count($value) > $olimpiada->limite_inscripciones) {
                        $fail("No puedes inscribirte en más de {$olimpiada->limite_inscripciones} niveles de competencia");
                    }
                }
            ],
            'listaPostulantes.*.inscripciones.*.idArea'      => ['required','exists:areas,id'],
            'listaPostulantes.*.inscripciones.*.idCategoria' => ['required','exists:categorias,id'],

            'listaPostulantes.*.contactos' => ['sometimes','array','min:1'],
            'listaPostulantes.*.contactos.*.telefono_contacto' => ['required_without:listaPostulantes.*.contactos.*.email_contacto', 'nullable','regex:/^[0-9]{7,8}$/'],
            'listaPostulantes.*.contactos.*.tipo_contacto_telefono' => ['required_with:listaPostulantes.*.contactos.*.telefono_contacto','nullable','integer','in:1,2,3,4'],
            'listaPostulantes.*.contactos.*.email_contacto' => ['required_without:listaPostulantes.*.contactos.*.telefono_contacto', 'nullable','email:rfc'],
            'listaPostulantes.*.contactos.*.tipo_contacto_email' => ['required_with:listaPostulantes.*.contactos.*.email_contacto','nullable','integer','in:1,2,3,4'],
        ];
    }

    public function messages()
    {
        return [
            'ci.required'       => 'El CI del responsable es obligatorio',
            // Quedamos sin 'ci.exists'
            'olimpiada_id.required' => 'El ID de la olimpiada es obligatorio',
            'olimpiada_id.exists'   => 'La olimpiada especificada no existe',
            'codigo_lista.exists'   => 'El código de lista no es válido',

            'listaPostulantes.required' => 'La lista de postulantes es obligatoria',
            'listaPostulantes.array'    => 'La lista de postulantes debe ser un arreglo',
            'listaPostulantes.min'      => 'Debe incluir al menos un postulante',

            'listaPostulantes.*.ci.required' => 'El CI es obligatorio',
            'listaPostulantes.*.ci.regex'    => 'Formato de CI inválido',
            'listaPostulantes.*.ci.max'      => 'El CI no puede exceder los 11 caracteres',

            'listaPostulantes.*.nombres.required'  => 'El nombre es obligatorio',
            'listaPostulantes.*.nombres.regex'     => 'El nombre no debe contener números',
            'listaPostulantes.*.apellidos.required'=> 'El apellido es obligatorio',
            'listaPostulantes.*.apellidos.regex'   => 'El apellido no debe contener números',

            'listaPostulantes.*.fecha_nacimiento.required'    => 'La fecha de nacimiento es obligatoria',
            'listaPostulantes.*.fecha_nacimiento.date_format' => 'La fecha debe tener formato dd-mm-yyyy',

            'listaPostulantes.*.correo_postulante.required' => 'El correo es obligatorio',
            'listaPostulantes.*.correo_postulante.email'    => 'El correo no es válido',

            'listaPostulantes.*.idDepartamento.required' => 'El departamento es obligatorio',
            'listaPostulantes.*.idDepartamento.exists'   => 'El departamento seleccionado no existe',
            'listaPostulantes.*.idProvincia.required'    => 'La provincia es obligatoria',
            'listaPostulantes.*.idProvincia.exists'      => 'La provincia seleccionada no existe',
            'listaPostulantes.*.idColegio.required'      => 'El colegio es obligatorio',
            'listaPostulantes.*.idColegio.exists'        => 'El colegio seleccionado no existe',
            'listaPostulantes.*.idCurso.required'        => 'El curso es obligatorio',
            'listaPostulantes.*.idCurso.between'         => 'El curso debe estar entre 1ro y 12vo',

            'listaPostulantes.*.inscripciones.required' => 'Las inscripciones son obligatorias',
            'listaPostulantes.*.inscripciones.min'      => 'Debe incluir al menos una inscripción',
            'listaPostulantes.*.inscripciones.*.idArea.required' => 'El área es obligatoria',
            'listaPostulantes.*.inscripciones.*.idArea.exists'   => 'Área no válida',
            'listaPostulantes.*.inscripciones.*.idCategoria.required' => 'La categoría es obligatoria',
            'listaPostulantes.*.inscripciones.*.idCategoria.exists'   => 'Categoría no válida',

            'listaPostulantes.*.telefono_contacto.required' => 'El teléfono es obligatorio',
            'listaPostulantes.*.telefono_contacto.regex'    => 'El teléfono debe tener 7 u 8 dígitos',
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
            $fecha = $post['fecha_nacimiento'] ?? null;
            if (is_string($fecha)) {
                try {
                    if (strpos($fecha, 'GMT') !== false) {
                        $fecha = new DateTime($fecha);
                        $this->merge(["listaPostulantes.$i.fecha_nacimiento" => $fecha->format('d-m-Y')]);
                    } elseif (preg_match('#^\d{4}-\d{2}-\d{2}$#', $fecha)) {
                        $fecha = Carbon::createFromFormat('Y-m-d', $fecha);
                        $this->merge(["listaPostulantes.$i.fecha_nacimiento" => $fecha->format('d-m-Y')]);
                    }
                } catch (\Exception $e) {
                    // Validación fallará más adelante
                }
            }

            if (!isset($post['inscripciones']) || !is_array($post['inscripciones'])) {
                $this->merge(["listaPostulantes.$i.inscripciones" => []]);
            }

            $requiredFields = [
                'nombres', 'apellidos', 'ci', 'fecha_nacimiento',
                'correo_postulante', 'telefono_contacto', 'tipo_contacto_telefono',
                'tipo_contacto_email', 'idDepartamento', 'idProvincia', 'idColegio', 'idCurso'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($post[$field])) {
                    $this->merge(["listaPostulantes.$i.$field" => null]);
                }
            }
        }
    }

    protected function failedValidation(Validator $validator)
    {
        $formatted = [];
        $errors = $validator->errors()->getMessages();

        foreach ($errors as $key => $messages) {
            if (in_array($key, ['ci', 'olimpiada_id', 'codigo_lista'])) {
                foreach ($messages as $msg) {
                    $formatted[] = $msg;
                }
                continue;
            }

            $parts = explode('.', $key);
            $index = $parts[1] ?? null;
            $fila = is_numeric($index) ? intval($index) + 1 : 'desconocida';
            $ci = $this->input("listaPostulantes.$index.ci") ?? 'desconocido';

            foreach ($messages as $msg) {
                $formatted[] = "error en inscripciones de la fila {$fila} del estudiante con CI {$ci}: {$msg}";
            }
        }

        throw new ValidationException($validator, response()->json([
            'errores' => $formatted
        ], 422));
    }
}
