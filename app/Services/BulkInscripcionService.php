<?php

namespace App\Services;

use App\Models\Lista;
use App\Models\Responsable;
use App\Models\Postulante;
use App\Models\NivelCompetencia;
use App\Models\Inscripcion;
use App\Models\Area;
use App\Models\Categoria;
use App\Models\Olimpiada;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BulkInscripcionService
{
    
    public function validateData(array $data): array
    {
        $errores    = [];
        $mapaCis    = []; // Mapa CI plano => fila original

        try {
            // 1. Obtener la Olimpiada y su límite de inscripciones por postulante
            $olimpiada           = Olimpiada::findOrFail($data['olimpiada_id']);
            $limitePorPostulante = $olimpiada->limite_inscripciones;

            foreach ($data['listaPostulantes'] as $index => $postulante) {
                $filaActual = $index + 1;
                $ciPlano    = $postulante['ci'];

                // === 1.1. Validar duplicados de CI en el archivo de entrada ===
                if (isset($mapaCis[$ciPlano])) {
                    $filaOriginal = $mapaCis[$ciPlano];
                    $errores[] = "error en inscripciones: fila {$filaOriginal} y fila {$filaActual} tienen CI iguales";
                    continue;
                }
                $mapaCis[$ciPlano] = $filaActual;

                // === 2. Buscar si el postulante ya existe en BD (comparando CI desencriptado) ===
                $postulanteExistente = Postulante::all(['id', 'ci', 'curso'])
                    ->first(function ($p) use ($ciPlano) {
                        try {
                            return Crypt::decryptString($p->getRawOriginal('ci')) === $ciPlano;
                        } catch (\Exception $e) {
                            return false;
                        }
                    });

                // === 3. Validar cambio de curso si existe en BD ===
                if ($postulanteExistente) {
                    $cursoPrevio = $postulanteExistente->curso;
                    $cursoNuevo  = $postulante['idCurso'];
                    if ((string)$cursoPrevio !== (string)$cursoNuevo) {
                        $literalAnterior = $this->cursoALiteral((int)$cursoPrevio);
                        $literalNuevo    = $this->cursoALiteral((int)$cursoNuevo);

                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ciPlano}: " .
                            "El postulante ya está inscrito anteriormente con curso {$literalAnterior} " .
                            "y no puede inscribirse ahora con curso {$literalNuevo}.";
                        continue;
                    }
                }

                // === 4. Contar inscripciones previas en la misma olimpiada y obtener combos previos ===
                $inscripcionesPrevias = 0;
                $combosPrevios        = [];
                if ($postulanteExistente) {
                    $queryPrevias = Inscripcion::where('postulante_id', $postulanteExistente->id)
                        ->whereHas('nivelCompetencia', function ($q2) use ($data) {
                            $q2->where('olimpiada_id', $data['olimpiada_id']);
                        });

                    $inscripcionesPrevias = $queryPrevias->count();

                    $combosPrevios = $queryPrevias
                        ->pluck('nivel_competencia_id')
                        ->map(function ($nivelId) {
                            $n = NivelCompetencia::with(['area', 'categoria'])->find($nivelId);
                            return $n->area_id . '-' . $n->categoria_id;
                        })
                        ->toArray();
                }

                // === 5. Contar cuántas inscripciones nuevas trae este postulante ===
                $inscripcionesNuevas = count($postulante['inscripciones'] ?? []);

                // === 6. Validar límite total (previas + nuevas) ===
                if ($inscripcionesPrevias + $inscripcionesNuevas > $limitePorPostulante) {
                    $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ciPlano}: " .
                        "No puede tener más de {$limitePorPostulante} inscripciones en esta olimpiada";
                    continue;
                }

                // === 7. Validar cada inscripción dentro del array de inscripciones ===
                $vistosPayload = [];
                foreach ($postulante['inscripciones'] as $i => $inscripcion) {
                    $areaId      = $inscripcion['idArea'];
                    $categoriaId = $inscripcion['idCategoria'];

                    // 7.1. Verificar existencia y vigencia de NivelCompetencia
                    $nivelCompetencia = NivelCompetencia::with(['area', 'categoria'])
                        ->where([
                            'area_id'      => $areaId,
                            'categoria_id' => $categoriaId,
                            'olimpiada_id' => $data['olimpiada_id'],
                            'vigente'      => true
                        ])->first();

                    if (! $nivelCompetencia) {
                        $nombreArea      = optional(Area::find($areaId))->nombre ?? "ID {$areaId}";
                        $nombreCategoria = optional(Categoria::find($categoriaId))->nombre ?? "ID {$categoriaId}";
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ciPlano}: " .
                            "Combinación de área {$nombreArea} y categoría {$nombreCategoria} no válida o no vigente";
                        continue;
                    }

                    // 7.2. Duplicado dentro del mismo payload
                    $keyPayload = "{$areaId}-{$categoriaId}";
                    if (in_array($keyPayload, $vistosPayload, true)) {
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ciPlano}: " .
                            "Área o categoría duplicada en la misma solicitud (área: {$nivelCompetencia->area->nombre}, " .
                            "categoría: {$nivelCompetencia->categoria->nombre})";
                    } else {
                        $vistosPayload[] = $keyPayload;
                    }

                    // 7.3. Duplicado contra inscripciones previas en BD
                    if (in_array($keyPayload, $combosPrevios, true)) {
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ciPlano}: " .
                            "Ya existe esta inscripción con área {$nivelCompetencia->area->nombre} " .
                            "y categoría {$nivelCompetencia->categoria->nombre}";
                    }

                    // 7.4. Validar que el curso corresponde a la categoría
                    $curso     = (int) $postulante['idCurso'];
                    $categoria = Categoria::find($categoriaId);
                    if (! $categoria) {
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ciPlano}: " .
                            "Categoría ID {$categoriaId} no encontrada";
                        continue;
                    }
                    if (! $this->validarCursoCategoria($curso, $categoriaId)) {
                        $literalCurso    = $this->cursoALiteral($curso);
                        $nombreCategoria = $categoria->nombre;
                        $errores[] = "error en inscripciones de la fila {$filaActual} del estudiante con CI {$ciPlano}: " .
                            "El curso {$literalCurso} no corresponde a la categoría {$nombreCategoria}";
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en validación masiva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        return $errores;
    }

    
    public function storeBulk(array $data): array
    {
        try {
            // 1. Validar datos antes de insertar nada
            $errores = $this->validateData($data);
            if (!empty($errores)) {
                return [
                    'mensaje' => 'Se encontraron errores. No se ha creado ninguna inscripción.',
                    'errores' => $errores
                ];
            }

            // 2. Ejecutar la transacción principal
            $resultado = DB::transaction(function () use ($data) {
                // 2.1. Obtener o crear Responsable, comparando CI desencriptado
                $responsable = $this->getOrCreateResponsable($data['ci']);

                // 2.2. Crear nueva Lista para esta masiva
                $lista = new Lista();
                $lista->codigo_lista    = strtoupper(Str::random(6));
                $lista->responsable_id  = $responsable->id;
                $lista->olimpiada_id    = $data['olimpiada_id'];
                $lista->estado          = 'Preinscrito';
                $lista->save();

                $exitosos = 0;

                // 2.3. Procesar cada postulante del array
                foreach ($data['listaPostulantes'] as $postulanteData) {
                    try {
                        // 2.3.1. Buscar existente comparando CI desencriptado
                        $ciPlano = $postulanteData['ci'];
                        $postulante = Postulante::all(['id', 'ci'])
                            ->first(function ($p) use ($ciPlano) {
                                try {
                                    return Crypt::decryptString($p->getRawOriginal('ci')) === $ciPlano;
                                } catch (\Exception $e) {
                                    return false;
                                }
                            });

                        if ($postulante) {
                            // Si existe, actualizar campos no cifrados
                            $postulante = Postulante::find($postulante->id);
                            $postulante->update([
                                'nombres'           => ucwords(strtolower($postulanteData['nombres'])),
                                'apellidos'         => ucwords(strtolower($postulanteData['apellidos'])),
                                'fecha_nacimiento'  => $postulanteData['fecha_nacimiento'],
                                'email'             => $postulanteData['correo_postulante'],
                                'curso'             => $postulanteData['idCurso'],
                                'provincia_id'      => $postulanteData['idProvincia'],
                            ]);
                        } else {
                            // Si no existe, crear nuevo postulante (mutator cifrará CI)
                            $postulante = Postulante::create([
                                'ci'                => $ciPlano,
                                'nombres'           => ucwords(strtolower($postulanteData['nombres'])),
                                'apellidos'         => ucwords(strtolower($postulanteData['apellidos'])),
                                'fecha_nacimiento'  => $postulanteData['fecha_nacimiento'],
                                'email'             => $postulanteData['correo_postulante'],
                                'curso'             => $postulanteData['idCurso'],
                                'provincia_id'      => $postulanteData['idProvincia'],
                            ]);
                        }

                        // 2.3.2. Reemplazar contactos
                        $postulante->contactos()->delete();
                        $postulante->contactos()->create([
                            'telefono'                => $postulanteData['telefono_contacto'] ?? null,
                            'tipo_contacto_telefono'  => $postulanteData['tipo_contacto_telefono'] ?? null,
                            'email'                   => $postulanteData['email_contacto'] ?? null,
                            'tipo_contacto_email'     => $postulanteData['tipo_contacto_email'] ?? null,
                        ]);

                        // 2.3.3. Crear cada Inscripción según los niveles indicados
                        foreach ($postulanteData['inscripciones'] as $inscripcionData) {
                            $nivelCompetencia = NivelCompetencia::where([
                                'area_id'      => $inscripcionData['idArea'],
                                'categoria_id' => $inscripcionData['idCategoria'],
                                'olimpiada_id' => $data['olimpiada_id']
                            ])->firstOrFail();

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
                    } catch (\Throwable $e) {
                        // Registrar el error pero continuar con el siguiente postulante
                        Log::error('Error procesando postulante en bulk', [
                            'ci'      => $postulanteData['ci'],
                            'error'   => $e->getMessage(),
                            'trace'   => $e->getTraceAsString()
                        ]);
                    }
                }

                return [
                    'codigo_lista' => $lista->codigo_lista,
                    'mensaje'      => 'Inscripción masiva completada',
                    'exitosos'     => $exitosos,
                ];
            });

            return $resultado;
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

    
    protected function validarCursoCategoria(int $curso, int $categoriaId): bool
    {
        $categoria = Categoria::find($categoriaId);
        if (! $categoria) {
            return false;
        }

        return $curso >= $categoria->minimo_grado && $curso <= $categoria->maximo_grado;
    }

    
    protected function getOrCreateResponsable(string $ciPlano): Responsable
    {
        // Intentar encontrar un responsable cuyo CI desencriptado coincida
        $responsable = Responsable::all(['id', 'ci'])
            ->first(function ($r) use ($ciPlano) {
                try {
                    return Crypt::decryptString($r->getRawOriginal('ci')) === $ciPlano;
                } catch (\Exception $e) {
                    return false;
                }
            });

        if ($responsable) {
            return Responsable::find($responsable->id);
        }

        // Si no existe, crearlo (mutator cifrará CI y nombre completo)
        try {
            return Responsable::create([
                'ci'               => $ciPlano,
                'nombre_completo'  => 'Responsable Temporal',
                'email'            => $ciPlano . '@example.com',
                'telefono'         => '00000000',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear responsable', [
                'ci'      => $ciPlano,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
