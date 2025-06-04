<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Provincia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt; 
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostulanteService
{
    public function procesarPostulante(array $data, $lista, $indice)
    {
        return DB::transaction(function () use ($data, $lista, $indice) {
            // 1) Validación
            $validator = Validator::make($data, $this->getValidationRules(), $this->getCustomMessages());
            $validator->setAttributeNames($this->getAttributeNames());

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            // 2) Comprobar que la provincia pertenece al departamento
            $provincia = Provincia::find($data['provincia']);
            if (! $provincia || $provincia->departamento_id != $data['departamento']) {
                throw new \Exception('La provincia no pertenece al departamento seleccionado');
            }

            // 3) Buscar POSTULANTE por CI desencriptado (si existe, lo actualizamos; si no, lo creamos)
            $ciPlano = $data['ci'];
            $postulante = Postulante::all()->first(function ($p) use ($ciPlano) {
                try {
                    return Crypt::decryptString($p->getRawOriginal('ci')) === $ciPlano;
                } catch (\Exception $e) {
                    return false;
                }
            });

            // Preparamos datos comunes
            $atributos = [
                'nombres'           => ucwords(strtolower($data['nombres'])),
                'apellidos'         => ucwords(strtolower($data['apellidos'])),
                'fecha_nacimiento'  => $data['fecha_nacimiento'],
                'email'             => $data['correo_postulante'],
                'curso'             => $data['curso'],
                'provincia_id'      => $data['provincia'],
            ];

            if ($postulante) {
                // 3a) Si existe, actualizar
                $postulante->update($atributos);
            } else {
                // 3b) Si no existe, crear uno nuevo (el modelo Postulante se encargará de cifrar 'ci', 'nombres' y 'apellidos')
                $postulante = Postulante::create(array_merge(
                    ['ci' => $ciPlano],
                    $atributos
                ));
            }

            // 4) Validar cuántas inscripciones ya tiene en esta olimpiada
            $inscripcionesExistentes = Inscripcion::whereHas('nivelCompetencia', function ($q) use ($lista) {
                $q->where('olimpiada_id', $lista->olimpiada_id);
            })->where('postulante_id', $postulante->id)->count();

            if (($inscripcionesExistentes + count($data['areas'])) > 2) {
                throw new \Exception('El postulante ya tiene ' . $inscripcionesExistentes . ' inscripciones en esta olimpiada');
            }

            return $postulante;
        });
    }

    private function getValidationRules()
    {
        return [
            'nombres'            => 'required|string|max:255',
            'apellidos'          => 'required|string|max:255',
            'ci'                 => 'required|string|max:10',
            'fecha_nacimiento'   => 'required|date',
            'correo_postulante'  => 'required|email',
            'curso'              => 'required|integer|between:1,12',
            'departamento'       => 'required|exists:departamentos,id',
            'provincia'          => 'required|exists:provincias,id',
            'areas'              => 'required|array|min:1|max:2',
            'areas.*.id_area'    => 'required|exists:areas,id',
            'areas.*.id_cat'     => 'required|exists:categorias,id',
        ];
    }

    private function getCustomMessages()
    {
        return [
            'required' => 'El campo :attribute es obligatorio',
            'exists'   => 'El valor seleccionado en :attribute no es válido',
            'areas.max'=> 'No puedes inscribirte en más de :max áreas',
            'between'  => 'El curso debe estar entre 1ro de primaria y 6to de secundaria'
        ];
    }

    private function getAttributeNames()
    {
        return [
            'nombres'           => 'nombres',
            'apellidos'         => 'apellidos',
            'ci'                => 'CI',
            'fecha_nacimiento'  => 'fecha de nacimiento',
            'correo_postulante' => 'correo del postulante',
            'curso'             => 'curso',
            'departamento'      => 'departamento',
            'provincia'         => 'provincia',
            'areas'             => 'áreas de inscripción',
            'areas.*.id_area'   => 'área',
            'areas.*.id_cat'    => 'categoría',
        ];
    }
}
