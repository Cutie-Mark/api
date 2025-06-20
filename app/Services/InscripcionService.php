<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Lista;
use App\Models\NivelCompetencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;

class InscripcionService
{
    public function crearInscripciones(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1) Validar que la provincia pertenezca al departamento
            $provincia = \App\Models\Provincia::find($data['provincia']);
            if (!$provincia || $provincia->departamento_id != $data['departamento']) {
                throw new \Exception('La provincia no pertenece al departamento seleccionado');
            }

            // 2) Determinar si el postulante ya existía y capturar su curso anterior
            $existingPostulante = null;
            $cursoAnterior = null;
            
            // Buscar postulante por CI desencriptado
            $allPostulantes = Postulante::all();
            foreach ($allPostulantes as $postulante) {
                if ($postulante->ci === $data['ci']) {
                    $existingPostulante = $postulante;
                    $cursoAnterior = $postulante->curso;
                    break;
                }
            }

            // 3) Crear o actualizar Postulante (los datos personales se actualizan aunque haya pagos pendientes)
            if ($existingPostulante) {
                $existingPostulante->update([
                    'nombres' => ucwords(strtolower($data['nombres'])),
                    'apellidos' => ucwords(strtolower($data['apellidos'])),
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'email' => $data['correo_postulante'],
                    'curso' => $data['curso'],
                    'provincia_id' => $data['provincia'],
                ]);
                $postulante = $existingPostulante;
                $postulanteCreado = false;
            } else {
                $postulante = Postulante::create([
                    'ci' => $data['ci'],
                    'nombres' => ucwords(strtolower($data['nombres'])),
                    'apellidos' => ucwords(strtolower($data['apellidos'])),
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'email' => $data['correo_postulante'],
                    'curso' => $data['curso'],
                    'provincia_id' => $data['provincia'],
                ]);
                $postulanteCreado = true;
            }

            $postulante->contactos()->delete(); // Eliminar contactos existentes
            foreach ($data['contactos'] as $contacto) {
                $postulante->contactos()->create([
                    'telefono' => $contacto['telefono_contacto'] ?? null,
                    'tipo_contacto_telefono' => $contacto['tipo_contacto_telefono'] ?? null,
                    'email' => $contacto['email_contacto'] ?? null,
                    'tipo_contacto_email' => $contacto['tipo_contacto_email'] ?? null,
                ]);
            }

            // 4) Obtener lista y olimpiada
            $lista               = Lista::with('olimpiada')->where('codigo_lista', $data['codigo_lista'])->firstOrFail();
            $olimpiadaId         = $lista->olimpiada_id;
            $limiteInscripciones = $lista->olimpiada->limite_inscripciones;

            // 5) Verificar si intentan cambiar el curso cuando ya hay inscripciones pagadas o completas
            if (!$postulanteCreado && $cursoAnterior !== null && $cursoAnterior != $data['curso']) {
                // Si hay al menos una inscripción con estado Pago Pendiente o Inscripcion Completa, se bloquea el cambio de curso
                $tienePagosOCompletas = Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', function ($q) use ($olimpiadaId) {
                        $q->where('olimpiada_id', $olimpiadaId);
                    })
                    ->whereIn('estado', ['Pago Pendiente', 'Inscripcion Completa'])
                    ->exists();

                if ($tienePagosOCompletas) {
                    throw new \Exception('Este postulante ya se encuentra en proceso de inscripcion, no se pueden cambiar los datos de inscripcion');
                }

                // Si no hay pagos pendientes ni inscripciones completas, se eliminan las inscripciones existentes
                Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', function ($q) use ($olimpiadaId) {
                        $q->where('olimpiada_id', $olimpiadaId);
                    })
                    ->delete();
            }

            // 6) Contar inscripciones previas (si se borraron, será 0; si no, es la cantidad ya existente)
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
                // 8.a) Verificar duplicados exactos (misma área y misma categoría)
                $duplicate = Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', function ($q2) use ($olimpiadaId, $areaInput) {
                        $q2->where('olimpiada_id', $olimpiadaId)
                           ->where('area_id', $areaInput['id_area'])
                           ->where('categoria_id', $areaInput['id_cat']);
                    })
                    ->exists();

                if ($duplicate) {
                    throw new \Exception('El postulante ya está inscrito en esa área y categoría');
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
                $cursoActual = (int) $data['curso'];
                if ($cursoActual < $nivel->categoria->minimo_grado || $cursoActual > $nivel->categoria->maximo_grado) {
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
