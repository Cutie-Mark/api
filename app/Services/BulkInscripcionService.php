<?php

namespace App\Services;

use App\Models\Lista;
use App\Models\Responsable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Postulante;
use App\Models\NivelCompetencia;
use App\Models\Inscripcion;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Olimpiada;

class BulkInscripcionService
{
    public function validateData(array $data)
    {
        $errores    = [];
        // Ahora usamos un mapa CI => fila_original
        $mapaCis    = [];

        try {
            // 1. Obtengo la olimpiada y su límite
            $olimpiada          = \App\Models\Olimpiada::findOrFail($data['olimpiada_id']);
            $limitePorPostulante = $olimpiada->limite_inscripciones;

            foreach ($data['listaPostulantes'] as $index => $postulante) {
                $filaActual = $index + 1;
                $ci         = $postulante['ci'];

                // === VALIDAR DUPLICADOS EN EXCEL (con mensaje personalizado) ===
                if (isset($mapaCis[$ci])) {
                    $filaOriginal = $mapaCis[$ci];
                    $errores[] = "error en inscripciones: fila {$filaOriginal} y fila {$filaActual} tienen CI iguales";
                    // No seguimos con las demás validaciones de esta fila
                    continue;
                } else {
                    // Guardamos la fila donde apareció este CI por primera vez
                    $mapaCis[$ci] = $filaActual;
                }
                // === FIN VALIDAR DUPLICADOS EN EXCEL ===

                // 2. Busco si el postulante ya existe en BD
                $postulanteExistente = \App\Models\Postulante::where('ci', $ci)->first();

                // 3. VALIDACIÓN DE CURSO DIFERENTE
                if ($postulanteExistente) {
                    $cursoPrevio = $postulanteExistente->curso;
                    $cursoNuevo  = $postulante['idCurso'];
                    if ($cursoPrevio !== $cursoNuevo) {
                        $literalAnterior = $this->cursoALiteral($cursoPrevio);
                        $literalNuevo    = $this->cursoALiteral($cursoNuevo);

                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ci}: " .
                                    "El postulante ya está inscrito anteriormente con curso {$literalAnterior} " .
                                    "y no puede inscribirse ahora con curso {$literalNuevo}.";
                        continue;
                    }
                }

                // 4. Cuento cuántas inscripciones previas y obtengo combos (área–categoría) que ya existían
                $inscripcionesPrevias = 0;
                $combosPrevios        = [];
                if ($postulanteExistente) {
                    $q = \App\Models\Inscripcion::where('postulante_id', $postulanteExistente->id)
                                                ->whereHas('nivelCompetencia', function($q2) use ($data) {
                                                    $q2->where('olimpiada_id', $data['olimpiada_id']);
                                                });
                    $inscripcionesPrevias = $q->count();

                    $combosPrevios = $q->pluck('nivel_competencia_id')
                                    ->map(function($nivelId) {
                                        $n = NivelCompetencia::with(['area','categoria'])->find($nivelId);
                                        return $n->area_id . '-' . $n->categoria_id;
                                    })->toArray();
                }

                // 5. Contar cuántas inscripciones nuevas trae el payload
                $inscripcionesNuevas = count($postulante['inscripciones']);

                // 6. Si excede el límite total (previas + nuevas), error
                if ($inscripcionesPrevias + $inscripcionesNuevas > $limitePorPostulante) {
                    $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ci}: " .
                                "No puede tener más de {$limitePorPostulante} inscripciones en esta olimpiada";
                    continue;
                }

                // 7. Ahora reviso cada inscripción individual
                $areasCategoriasVistas = [];
                foreach ($postulante['inscripciones'] as $i => $inscripcion) {
                    $areaId      = $inscripcion['idArea'];
                    $categoriaId = $inscripcion['idCategoria'];

                    // 7.1 Verificar existencia y vigencia de NivelCompetencia
                    $nivelCompetencia = NivelCompetencia::with(['area','categoria'])
                        ->where([
                            'area_id'      => $areaId,
                            'categoria_id' => $categoriaId,
                            'olimpiada_id' => $data['olimpiada_id'],
                            'vigente'      => true
                        ])->first();

                    if (! $nivelCompetencia) {
                        $areaModelo      = Area::find($areaId);
                        $categoriaModelo = Categoria::find($categoriaId);
                        $nombreArea      = $areaModelo ? $areaModelo->nombre : "ID {$areaId}";
                        $nombreCategoria = $categoriaModelo ? $categoriaModelo->nombre : "ID {$categoriaId}";

                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ci}: " .
                                    "Combinación de área {$nombreArea} y categoría {$nombreCategoria} no válida o no vigente";
                        continue;
                    }

                    // 7.2 Duplicado dentro de la misma solicitud (payload)
                    $keyPayload = "{$areaId}-{$categoriaId}";
                    if (in_array($keyPayload, $areasCategoriasVistas, true)) {
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ci}: " .
                                    "Área o categoría duplicada en la misma solicitud (área: {$nivelCompetencia->area->nombre}, " .
                                    "categoría: {$nivelCompetencia->categoria->nombre})";
                    } else {
                        $areasCategoriasVistas[] = $keyPayload;
                    }

                    // 7.3 Duplicado contra inscripciones previas en BD
                    if (in_array($keyPayload, $combosPrevios, true)) {
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ci}: " .
                                    "Ya existe esta inscripción con área {$nivelCompetencia->area->nombre} " .
                                    "y categoría {$nivelCompetencia->categoria->nombre}";
                    }

                    // 7.4 Validar que el curso corresponde a la categoría
                    $curso     = $postulante['idCurso'];
                    $categoria = Categoria::find($categoriaId);
                    if (! $categoria) {
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ci}: " .
                                    "Categoría ID {$categoriaId} no encontrada";
                        continue;
                    }
                    if (! $this->validarCursoCategoria($curso, $categoriaId)) {
                        $literalCurso    = $this->cursoALiteral($curso);
                        $nombreCategoria = $categoria->nombre;
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ci}: " .
                                    "El curso {$literalCurso} no corresponde a la categoría {$nombreCategoria}";
                    }
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en validación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        return $errores;
    }




    public function storeBulk(array $data)
    {
        try {
            // Validar los datos antes de cualquier inserción
            $errores = $this->validateData($data);
            if (!empty($errores)) {
                return [
                    'mensaje' => 'Se encontraron errores. No se ha creado ninguna inscripción.',
                    'errores' => $errores
                ];
            }

            $result = DB::transaction(function () use ($data) {
                // 1. Crear o recuperar el responsable
                $responsable = $this->getOrCreateResponsable($data['ci']);

                // 2. Crear la lista
                $lista = new Lista();
                $lista->codigo_lista = strtoupper(Str::random(6));
                $lista->responsable_id = $responsable->id;
                $lista->olimpiada_id = $data['olimpiada_id'];
                $lista->estado = 'Preinscrito';
                $lista->save();

                $exitosos = 0;

                // 3. Procesar cada postulante (igual que antes)
                foreach ($data['listaPostulantes'] as $postulanteData) {
                try {
                    $postulante = Postulante::updateOrCreate(
                        ['ci' => $postulanteData['ci']],
                        [
                            'nombres'          => ucwords(strtolower($postulanteData['nombres'])),
                            'apellidos'        => ucwords(strtolower($postulanteData['apellidos'])),
                            'fecha_nacimiento' => $postulanteData['fecha_nacimiento'],
                            'email'            => $postulanteData['correo_postulante'],
                            'provincia_id'     => $postulanteData['idProvincia'],
                            'curso'            => $postulanteData['idCurso']
                        ]
                    );

                    // ELIMINAR CONTACTOS EXISTENTES Y CREAR NUEVOS
                    $postulante->contactos()->delete();
                    $postulante->contactos()->create([
                        'telefono' => $postulanteData['telefono_contacto'] ?? null,
                        'tipo_contacto_telefono' => $postulanteData['tipo_contacto_telefono'] ?? null,
                        'email' => $postulanteData['email_contacto'] ?? null,
                        'tipo_contacto_email' => $postulanteData['tipo_contacto_email'] ?? null,
                    ]);

                    foreach ($postulanteData['inscripciones'] as $inscripcionData) {
                        $nivelCompetencia = NivelCompetencia::where([
                            'area_id'      => $inscripcionData['idArea'],
                            'categoria_id' => $inscripcionData['idCategoria'],
                            'olimpiada_id' => $data['olimpiada_id']
                        ])->firstOrFail();

                        // CREAR INSCRIPCIÓN SIN CAMPOS DE CONTACTO
                        Inscripcion::create([
                            'postulante_id'        => $postulante->id,
                            'responsable_id'       => $responsable->id,
                            'nivel_competencia_id' => $nivelCompetencia->id,
                            'lista_id'             => $lista->id,
                            'colegio_id'           => $postulanteData['idColegio'],
                            'orden_pago_id'        => null,
                            'estado'               => 'Preinscrito',
                        ]);

                        $exitosos++;
                    }
                } catch (\Exception $e) {
                    // ... manejo de errores ...
                }
            }

            return [
                'codigo_lista' => $lista->codigo_lista,
                'mensaje'      => 'Inscripción Completada',
                'exitosos'     => $exitosos
            ];
        });

        return $result;

        } catch (\Exception $e) {
            Log::error('Error en inscripción masiva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'mensaje' => 'Error al procesar la inscripción masiva',
                'error'   => $e->getMessage(),
                'errores' => ['Error de sistema: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Convierte el número de curso (1–12) en una descripción literal.
     * 1–6 corresponden a 1º–6º de primaria; 7–12 corresponden a 1º–6º de secundaria.
     */
    protected function cursoALiteral(int $curso): string
    {
        $ordinales = [
            1  => '1ero de primaria',
            2  => '2do de primaria',
            3  => '3ro de primaria',
            4  => '4to de primaria',
            5  => '5to de primaria',
            6  => '6to de primaria',
            7  => '1ero de secundaria',
            8  => '2do de secundaria',
            9  => '3ro de secundaria',
            10 => '4to de secundaria',
            11 => '5to de secundaria',
            12 => '6to de secundaria',
        ];

        return $ordinales[$curso] ?? "{$curso}";
    }

    protected function procesarPostulantesBulk(array $postulantes, Lista $lista): int
    {
        $exitosos = 0;

        foreach ($postulantes as $p) {
            try {
                // Crear o actualizar Postulante
                $fechaNacimiento = $p['fecha_nacimiento'];
                $postulante = Postulante::updateOrCreate(
                    [ 'ci' => $p['ci'] ],
                    [
                        'nombres'          => ucwords(strtolower($p['nombres'])),
                        'apellidos'        => ucwords(strtolower($p['apellidos'])),
                        'fecha_nacimiento' => $fechaNacimiento,
                        'email'            => $p['correo_postulante'],
                        'curso'            => $p['idCurso'],
                        'provincia_id'     => $p['idProvincia']
                    ]
                );
                Log::info('Postulante procesado', ['postulante_id' => $postulante->id]);
                
                $postulante->contactos()->delete();
                $postulante->contactos()->create([
                    'telefono' => $p['telefono_contacto'] ?? null,
                    'tipo_contacto_telefono' => $p['tipo_contacto_telefono'] ?? null,
                    'email' => $p['email_contacto'] ?? null,
                    'tipo_contacto_email' => $p['tipo_contacto_email'] ?? null, ]);

                //  b) Para cada inscripción enviada, crear la Inscripción
                foreach ($p['inscripciones'] as $inscripcionDato) {
                    //  b.1) Obtener el NivelCompetencia correspondiente
                    $nivel = NivelCompetencia::where('area_id', $inscripcionDato['idArea'])
                                ->where('categoria_id', $inscripcionDato['idCategoria'])
                                ->where('olimpiada_id', $lista->olimpiada_id)
                                ->firstOrFail();

                    Log::info('NivelCompetencia encontrado', ['nivel_id' => $nivel->id]);

                    //  b.2) Crear la Inscripción
                    Inscripcion::create([
                        'postulante_id'         => $postulante->id,
                        'responsable_id'        => $lista->responsable_id,
                        'nivel_competencia_id'  => $nivel->id,
                        'colegio_id'            => $p['idColegio'],
                        'orden_pago_id'         => null,
                        'lista_id'              => $lista->id,
                        'estado'                => 'Preinscrito',
                    ]);

                    Log::info('Inscripción creada', [
                        'postulante_id' => $postulante->id,
                        'nivel_id'      => $nivel->id,
                        'lista_id'      => $lista->id
                    ]);
                }

                // Incrementar contador de inscripciones exitosas por cada inscripción
                foreach ($p['inscripciones'] as $inscripcion) {
                    $exitosos++;
                }

            } catch (\Throwable $e) {
                // Registrar el error y continuar con el siguiente postulante
                Log::error('Error procesando postulante en bulk', [
                    'ci'      => $p['ci'],
                    'mensaje' => $e->getMessage(),
                    'traza'   => $e->getTraceAsString()
                ]);
            }
        }

        return $exitosos;
    }

    protected function validarCursoCategoria($curso, $categoriaId): bool
    {
        $categoria = \App\Models\Categoria::find($categoriaId);
        if (!$categoria) {
            return false;
        }

        // Validar que el curso está dentro del rango permitido por la categoría
        return $curso >= $categoria->minimo_grado && $curso <= $categoria->maximo_grado;
    }

    protected function getOrCreateResponsable($ci)
    {
        try {
            return Responsable::firstOrCreate(
                ['ci' => $ci],
                [
                    'nombre'     => 'Responsable Temporal',
                    'apellido'   => 'Pendiente',
                    'telefono'   => '00000000',
                    'es_profesor'=> false,
                    'email'      => $ci . '@example.com'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error al crear o recuperar responsable', [
                'ci'    => $ci,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
