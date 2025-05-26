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


class BulkInscripcionService
{
    public function validateData(array $data)
    {
        $errores = [];
        
        try {
            foreach ($data['listaPostulantes'] as $index => $postulante) {
                $fila = $index + 1;
                
                // 1. Obtener el límite de inscripciones de la olimpiada
                $olimpiada = \App\Models\Olimpiada::findOrFail($data['olimpiada_id']);
                if (count($postulante['inscripciones']) > $olimpiada->limite_inscripciones) {
                    $errores[] = "error en inscripciones de la fila {$fila} del estudiante con CI {$postulante['ci']}: Máximo {$olimpiada->limite_inscripciones} inscripciones permitidas";
                    continue;
                }

                // 2. Validar existencia de postulante previo
                $postulanteExistente = \App\Models\Postulante::where('ci', $postulante['ci'])->first();
                if ($postulanteExistente) {
                    $inscripcionesExistentes = \App\Models\Inscripcion::where('postulante_id', $postulanteExistente->id)
                        ->whereHas('nivelCompetencia', function($q) use ($data) {
                            $q->where('olimpiada_id', $data['olimpiada_id']);
                        })->count();
                        
                    if ($inscripcionesExistentes > 0) {
                        $errores[] = "error en la fila {$fila}: El estudiante con CI {$postulante['ci']} ya está inscrito en esta olimpiada";
                        continue;
                    }
                }

                // 3. Validar áreas y categorías duplicadas
                $areasCategoriasVistas = [];
                foreach ($postulante['inscripciones'] as $inscripcion) {
                    // 3.1 Verificar que el nivel de competencia existe y está vigente
                    $nivelCompetencia = \App\Models\NivelCompetencia::with(['area', 'categoria'])
                        ->where([
                            'area_id' => $inscripcion['idArea'],
                            'categoria_id' => $inscripcion['idCategoria'],
                            'olimpiada_id' => $data['olimpiada_id'],
                            'vigente' => true
                        ])->first();

                    if (!$nivelCompetencia) {
                        $errores[] = "error en inscripciones de la fila {$fila} del estudiante con CI {$postulante['ci']}: " . 
                                   "Combinación de área {$inscripcion['idArea']} y categoría {$inscripcion['idCategoria']} no válida o no vigente";
                        continue;
                    }

                    // 3.2 Verificar duplicados
                    $key = $inscripcion['idArea'] . '-' . $inscripcion['idCategoria'];
                    if (in_array($key, $areasCategoriasVistas)) {
                        $errores[] = "error en inscripciones de la fila {$fila} del estudiante con CI {$postulante['ci']}: " . 
                                   "Área o categoría duplicada (área: {$nivelCompetencia->area->nombre}, categoría: {$nivelCompetencia->categoria->nombre})";
                    } else {
                        $areasCategoriasVistas[] = $key;
                    }

                    // 3.3 Validar que el curso corresponde a la categoría
                    $cursoValido = $this->validarCursoCategoria($postulante['idCurso'], $nivelCompetencia->categoria_id);
                    if (!$cursoValido) {
                        $errores[] = "error en inscripciones de la fila {$fila} del estudiante con CI {$postulante['ci']}: " . 
                                   "El curso {$postulante['idCurso']} no corresponde a la categoría {$nivelCompetencia->categoria->nombre}";
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
            // Validar los datos antes de realizar cualquier inserción
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
                $lista->estado = 'Preinscrito';  // Changed to a valid enum value
                $lista->save();

                $exitosos = 0;

                // 3. Procesar cada postulante
                foreach ($data['listaPostulantes'] as $postulanteData) {
                    try {
                        // Crear o actualizar postulante
                        $postulante = Postulante::updateOrCreate(
                            ['ci' => $postulanteData['ci']],
                            [
                                'nombres' => ucwords(strtolower($postulanteData['nombres'])),
                                'apellidos' => ucwords(strtolower($postulanteData['apellidos'])),
                                'fecha_nacimiento' => $postulanteData['fecha_nacimiento'],
                                'email' => $postulanteData['correo_postulante'],
                                'provincia_id' => $postulanteData['idProvincia'],
                                'curso' => $postulanteData['idCurso']
                            ]
                        );

                        // Procesar cada inscripción del postulante
                        foreach ($postulanteData['inscripciones'] as $inscripcionData) {
                            $nivelCompetencia = NivelCompetencia::where([
                                'area_id' => $inscripcionData['idArea'],
                                'categoria_id' => $inscripcionData['idCategoria'],
                                'olimpiada_id' => $data['olimpiada_id']
                            ])->firstOrFail();

                            Inscripcion::create([
                                'postulante_id' => $postulante->id,
                                'responsable_id' => $responsable->id,
                                'nivel_competencia_id' => $nivelCompetencia->id,
                                'lista_id' => $lista->id,
                                'colegio_id' => $postulanteData['idColegio'],
                                'orden_pago_id' => null,
                                'email' => $postulanteData['email_contacto'],
                                'telefono' => $postulanteData['telefono_contacto'],
                                'tipo_contacto_email' => $postulanteData['tipo_contacto_email'],
                                'tipo_contacto_telefono' => $postulanteData['tipo_contacto_telefono'],
                                'estado' => 'Preinscrito',
                                'fecha_inscripcion' => now()
                            ]);

                            $exitosos++;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error al procesar postulante', [
                            'postulante' => $postulanteData['ci'],
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e; // Re-lanzar para que el transaction se revierta
                    }
                }

                return [
                    'codigo_lista' => $lista->codigo_lista,
                    'mensaje' => 'Inscripción Completada',
                    'exitosos' => $exitosos
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
                'error' => $e->getMessage(),
                'errores' => ['Error de sistema: ' . $e->getMessage()]
            ];
        }
    }

    protected function obtenerOLista(array $data): Lista
    {
        // 1) Obtener responsable por CI (o lanzar ModelNotFoundException)
        $responsable = Responsable::where('ci', $data['ci'])->firstOrFail();

        // 2) Si cadena 'codigo_lista' vino con valor, buscarla y retornarla
        if (!empty($data['codigo_lista'])) {
            return Lista::where('codigo_lista', $data['codigo_lista'])->firstOrFail();
        }

        // 3) Si no enviaron código, generar uno nuevo
        do {
            $codigo = Str::upper(Str::random(6));
        } while (Lista::where('codigo_lista', $codigo)->exists());

        // 4) Crear nueva lista vinculada a este responsable y a la Olimpiada
        return $responsable->listas()->create([
            'codigo_lista' => $codigo,
            'olimpiada_id' => $data['olimpiada_id'],
            'estado'       => 'Preinscrito',
        ]);
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
                        'email'                 => $p['email_contacto'],
                        'tipo_contacto_email'   => $p['tipo_contacto_email'],
                        'telefono'              => $p['telefono_contacto'],
                        'tipo_contacto_telefono'=> $p['tipo_contacto_telefono'],
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
                    'nombre' => 'Responsable Temporal',
                    'apellido' => 'Pendiente',
                    'telefono' => '00000000',
                    'es_profesor' => false,  // valor por defecto
                    'email' => $ci . '@example.com'  // email temporal
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error al crear o recuperar responsable', [
                'ci' => $ci,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
