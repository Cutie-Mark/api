<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Provincia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PostulanteService
{
    public function procesarPostulante(array $data, $lista, $indice)
    {
        return DB::transaction(function () use ($data, $lista, $indice) {
            // 1. Validación
            $validator = Validator::make($data, $this->getValidationRules(), $this->getCustomMessages());
            $validator->setAttributeNames($this->getAttributeNames());

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $provincia = Provincia::find($data['provincia']);
            if (!$provincia || $provincia->departamento_id != $data['departamento']) {
                throw new \Exception('La provincia no pertenece al departamento seleccionado');
            }

            // 2. Buscar postulante por CI desencriptado
            $existingPostulante = null;
            $allPostulantes = Postulante::all();
            foreach ($allPostulantes as $postulante) {
                if ($postulante->ci === $data['ci']) {
                    $existingPostulante = $postulante;
                    break;
                }
            }

            // 3. Crear o actualizar el postulante
            if ($existingPostulante) {
                $existingPostulante->update([
                    'nombres' => ucwords(strtolower($data['nombres'])),
                    'apellidos' => ucwords(strtolower($data['apellidos'])),
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'email' => $data['correo_postulante'],
                    'curso' => $data['curso'],
                    'provincia_id' => $data['provincia']
                ]);
                $postulante = $existingPostulante;
            } else {
                $postulante = Postulante::create([
                    'ci' => $data['ci'],
                    'nombres' => ucwords(strtolower($data['nombres'])),
                    'apellidos' => ucwords(strtolower($data['apellidos'])),
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'email' => $data['correo_postulante'],
                    'curso' => $data['curso'],
                    'provincia_id' => $data['provincia']
                ]);
            }

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
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'ci' => 'required|string|max:10',
            'fecha_nacimiento' => 'required|date',
            'correo_postulante' => 'required|email',
            'curso' => 'required|integer|between:1,12',
            'departamento' => 'required|exists:departamentos,id',
            'provincia' => 'required|exists:provincias,id',
            'areas' => 'required|array|min:1|max:2',
            'areas.*.id_area' => 'required|exists:areas,id',
            'areas.*.id_cat' => 'required|exists:categorias,id',
        ];
    }

    private function getCustomMessages()
    {
        return [
            'required' => 'El campo :attribute es obligatorio',
            'exists' => 'El valor seleccionado en :attribute no es válido',
            'areas.max' => 'No puedes inscribirte en más de :max áreas',
            'between' => 'El curso debe estar entre 1ro de primaria y 6to de secundaria'
        ];
    }

    private function getAttributeNames()
    {
        return [
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'ci' => 'CI',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'correo_postulante' => 'correo del postulante',
            'curso' => 'curso',
            'departamento' => 'departamento',
            'provincia' => 'provincia',
            'areas' => 'áreas de inscripción',
            'areas.*.id_area' => 'área',
            'areas.*.id_cat' => 'categoría',
        ];
    }
}
