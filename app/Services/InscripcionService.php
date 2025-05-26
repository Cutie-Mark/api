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
     * @param array $data Debe contener:
     *    - nombres, apellidos, ci, fecha_nacimiento (Y-m-d), correo_postulante,     *      curso, departamento, provincia, niveles_competencia (array con id_area e id_cat), 
     *      email_contacto, tipo_contacto_email, telefono_contacto, tipo_contacto_telefono,
     *      colegio, codigo_lista
     */
    public function crearInscripciones(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1) Validar que la provincia pertenezca al departamento
            $provincia = \App\Models\Provincia::find($data['provincia']);
            if (! $provincia || $provincia->departamento_id != $data['departamento']) {
                throw new \Exception('La provincia no pertenece al departamento seleccionado');
            }

            // 2) Crear o actualizar Postulante
            $postulante = Postulante::updateOrCreate(
                ['ci' => $data['ci']],
                [
                    'nombres'          => ucwords(strtolower($data['nombres'])),
                    'apellidos'        => ucwords(strtolower($data['apellidos'])),
                    'fecha_nacimiento' => $data['fecha_nacimiento'],   // → CORRECCIÓN: ya viene en Y-m-d
                    'email'            => $data['correo_postulante'],
                    'curso'            => $data['curso'],
                    'provincia_id'     => $data['provincia']
                ]
            );

            // 3) Obtener lista (lanza ModelNotFoundException si no existe) y su olimpiada relacionada
            $lista = Lista::with('olimpiada')->where('codigo_lista', $data['codigo_lista'])->firstOrFail();
            $olimpiadaId = $lista->olimpiada_id;
            $limiteInscripciones = $lista->olimpiada->limite_inscripciones;

            // 4) Contar inscripciones previas en esta olimpiada
            $insCount = Inscripcion::where('postulante_id', $postulante->id)
                ->whereHas('nivelCompetencia', function ($q) use ($olimpiadaId) {
                    $q->where('olimpiada_id', $olimpiadaId);
                })
                ->count();
                
            // 5) Validar máximo de inscripciones según la olimpiada
            if ($insCount + count($data['niveles_competencia']) > $limiteInscripciones) {
                throw new \Exception("Un estudiante no puede tener más de {$limiteInscripciones} inscripciones en la misma olimpiada");
            }

            // 6) Crear inscripciones para cada nivel de competencia
            foreach ($data['niveles_competencia'] as $areaInput) {
                // 6.a) Verificar duplicados por área o categoría en la misma olimpiada
                $duplicate = Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', function ($q2) use ($olimpiadaId, $areaInput) {
                        $q2->where('olimpiada_id', $olimpiadaId)
                           ->where(function($q3) use ($areaInput) {
                               $q3->where('area_id', $areaInput['id_area'])
                                  ->orWhere('categoria_id', $areaInput['id_cat']);
                           });
                    })
                    ->exists();

                if ($duplicate) {
                    throw new \Exception('El postulante ya está inscrito en esa área o categoría');
                }

                // 6.b) Obtener el NivelCompetencia y validar que exista
                $nivel = NivelCompetencia::where('area_id', $areaInput['id_area'])
                    ->where('categoria_id', $areaInput['id_cat'])
                    ->where('olimpiada_id', $olimpiadaId)
                    ->with('categoria')  // Eager load la relación con categoría
                    ->first();

                if (! $nivel) {
                    throw new \Exception('La combinación área-categoría no es válida para la olimpiada seleccionada');
                }                // 6.c) Validar que el curso del postulante esté dentro del rango permitido
                $curso = (int) $data['curso'];
                if ($curso < $nivel->categoria->minimo_grado || $curso > $nivel->categoria->maximo_grado) {
                    $categoria = $nivel->categoria->nombre;
                    $minimo = $nivel->categoria->minimo_grado;
                    $maximo = $nivel->categoria->maximo_grado;
                    
                    // Convertir números a texto de grado
                    $gradoMinimo = $this->numeroAGrado($minimo);
                    $gradoMaximo = $this->numeroAGrado($maximo);
                    
                    // Si el rango es el mismo grado, mostrar solo uno
                    $mensajeGrados = $minimo === $maximo 
                        ? $gradoMinimo 
                        : "{$gradoMinimo} a {$gradoMaximo}";
                        
                    throw new \Exception("La categoría {$categoria} solo acepta estudiantes de {$mensajeGrados}");
                }

                // 6.d) Crear la Inscripción
                Inscripcion::create([
                    'postulante_id'          => $postulante->id,
                    'responsable_id'         => $lista->responsable_id,
                    'nivel_competencia_id'   => $nivel->id,
                    'colegio_id'             => $data['colegio'],           // → CORRECCIÓN: usar $data['colegio']
                    'orden_pago_id'          => null,
                    'lista_id'               => $lista->id,
                    'email'                  => $data['email_contacto'],
                    'tipo_contacto_email'    => $data['tipo_contacto_email'],
                    'telefono'               => $data['telefono_contacto'],
                    'tipo_contacto_telefono' => $data['tipo_contacto_telefono'],
                    'estado'                 => 'Preinscrito'
                ]);
            }

            return 'Inscripción creada exitosamente';
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
            default => $numero
        };

        if ($numero <= 6) {
            return $ordinal . ' de primaria';
        } else {
            // Para secundaria, restamos 6 para obtener el grado correcto (7-12 -> 1-6)
            return $ordinal . ' de secundaria';
        }
    }
}
