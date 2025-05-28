<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Lista;
use App\Models\NivelCompetencia;
use Illuminate\Support\Facades\DB;

class InscripcionService
{
    /**
     * Crea una (o más) inscripciones para un postulante individual.
     * Si el postulante ya existía y se detecta que cambió de curso,
     * borra primero todas sus inscripciones en esta olimpiada para que pueda agregar otras.
     *
     * @param array $data Debe contener:
     *    - nombres, apellidos, ci, fecha_nacimiento (Y-m-d), correo_postulante,
     *      curso, departamento, provincia, niveles_competencia (array con id_area e id_cat),
     *      email_contacto, tipo_contacto_email, telefono_contacto, tipo_contacto_telefono,
     *      colegio, codigo_lista
     *
     * @return string Mensaje indicando creación o actualización
     */
    public function crearInscripciones(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1) Validar que la provincia pertenezca al departamento
            $provincia = \App\Models\Provincia::find($data['provincia']);
            if (!$provincia || $provincia->departamento_id != $data['departamento']) {
                throw new \Exception('La provincia no pertenece al departamento seleccionado');
            }

            // 2) Determinar si el postulante ya existía y capturar su curso anterior
            $existingPostulante = Postulante::where('ci', $data['ci'])->first();
            $cursoAnterior      = $existingPostulante ? $existingPostulante->curso : null;

            // 3) Crear o actualizar Postulante
            $postulante = Postulante::updateOrCreate(
                ['ci' => $data['ci']],
                [
                    'nombres'          => ucwords(strtolower($data['nombres'])),
                    'apellidos'        => ucwords(strtolower($data['apellidos'])),
                    'fecha_nacimiento' => $data['fecha_nacimiento'],   // ya viene en Y-m-d
                    'email'            => $data['correo_postulante'],
                    'curso'            => $data['curso'],
                    'provincia_id'     => $data['provincia'],
                ]
            );

            // Saber si fue recién creado (para el mensaje final)
            $postulanteCreado = $postulante->wasRecentlyCreated;

            // 4) Obtener lista y olimpiada
            $lista               = Lista::with('olimpiada')->where('codigo_lista', $data['codigo_lista'])->firstOrFail();
            $olimpiadaId         = $lista->olimpiada_id;
            $limiteInscripciones = $lista->olimpiada->limite_inscripciones;

            // 5) Si este postulante existía y cambió el curso, eliminar todas sus inscripciones en esta olimpiada
            if (!$postulanteCreado && $cursoAnterior !== null && $cursoAnterior != $data['curso']) {
                Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', function ($q) use ($olimpiadaId) {
                        $q->where('olimpiada_id', $olimpiadaId);
                    })
                    ->delete();
            }

            // 6) Contar inscripciones previas (puede ser 0 si acabamos de borrarlas)
            $insCount = Inscripcion::where('postulante_id', $postulante->id)
                ->whereHas('nivelCompetencia', function ($q) use ($olimpiadaId) {
                    $q->where('olimpiada_id', $olimpiadaId);
                })
                ->count();

            // 7) Validar máximo de inscripciones según la olimpiada
            if ($insCount + count($data['niveles_competencia']) > $limiteInscripciones) {
                throw new \Exception("Un estudiante no puede tener más de {$limiteInscripciones} inscripciones en la misma olimpiada");
            }

            // 8) Crear inscripciones para cada nivel de competencia
            foreach ($data['niveles_competencia'] as $areaInput) {
                // 8.a) Verificar duplicados por categoría en esta olimpiada
                $duplicate = Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', function ($q2) use ($olimpiadaId, $areaInput) {
                        $q2->where('olimpiada_id', $olimpiadaId)
                           ->where('categoria_id', $areaInput['id_cat']);
                    })
                    ->exists();

                if ($duplicate) {
                    throw new \Exception('El postulante ya está inscrito en esa categoría');
                }

                // 8.b) Obtener el NivelCompetencia y validar que exista
                $nivel = NivelCompetencia::where('area_id', $areaInput['id_area'])
                    ->where('categoria_id', $areaInput['id_cat'])
                    ->where('olimpiada_id', $olimpiadaId)
                    ->with('categoria')
                    ->first();

                if (! $nivel) {
                    throw new \Exception('La combinación área-categoría no es válida para la olimpiada seleccionada');
                }

                // 8.c) Validar rango de curso vs categoría
                $curso = (int) $data['curso'];
                if ($curso < $nivel->categoria->minimo_grado || $curso > $nivel->categoria->maximo_grado) {
                    $categoria = $nivel->categoria->nombre;
                    $minimo    = $nivel->categoria->minimo_grado;
                    $maximo    = $nivel->categoria->maximo_grado;

                    $gradoMinimo = $this->numeroAGrado($minimo);
                    $gradoMaximo = $this->numeroAGrado($maximo);
                    $mensajeGrados = $minimo === $maximo
                        ? $gradoMinimo
                        : "{$gradoMinimo} a {$gradoMaximo}";

                    throw new \Exception("La categoría {$categoria} solo acepta estudiantes de {$mensajeGrados}");
                }

                // 8.d) Crear la Inscripción
                Inscripcion::create([
                    'postulante_id'          => $postulante->id,
                    'responsable_id'         => $lista->responsable_id,
                    'nivel_competencia_id'   => $nivel->id,
                    'colegio_id'             => $data['colegio'],
                    'orden_pago_id'          => null,
                    'lista_id'               => $lista->id,
                    'email'                  => $data['email_contacto'],
                    'tipo_contacto_email'    => $data['tipo_contacto_email'],
                    'telefono'               => $data['telefono_contacto'],
                    'tipo_contacto_telefono' => $data['tipo_contacto_telefono'],
                    'estado'                 => 'Preinscrito'
                ]);
            }

            // 9) Devolver mensaje según creación o actualización
            if ($postulanteCreado) {
                return 'Inscripción creada exitosamente';
            } else {
                return 'Datos de inscripción actualizados correctamente';
            }
        });
    }

    /**
     * Convierte un número de grado (1-12) a su representación en texto
     */
    private function numeroAGrado(int $numero): string
    {
        $ordinal = match($numero % 6) {
            1 => '1ro',
            2 => '2do',
            3 => '3ro',
            4 => '4to',
            5 => '5to',
            0 => '6to',
            default => (string)$numero
        };

        if ($numero <= 6) {
            return $ordinal . ' de primaria';
        } else {
            return $ordinal . ' de secundaria';
        }
    }
}
