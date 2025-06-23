<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Provincia;
use App\Models\Responsable;
use App\Models\Lista;
use App\Models\NivelCompetencia;
use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Olimpiada;
use App\Services\InscripcionService;
use App\Services\InscripcionQueryService;
use App\Services\PostulanteService;
use App\Services\CategoriaService;
use App\Services\BulkInscripcionService;
use App\Http\Requests\StoreInscripcionRequest;
use App\Http\Requests\BulkInscripcionRequest;

class InscripcionController extends Controller
{
    protected $inscripcionService;
    protected $inscripcionQueryService;
    protected $postulanteService;
    protected $categoriaService;
    protected $bulkInscripcionService;

    public function __construct(
        InscripcionService $inscripcionService,
        InscripcionQueryService $inscripcionQueryService,
        PostulanteService $postulanteService,
        CategoriaService $categoriaService,
        BulkInscripcionService $bulkInscripcionService
    ) {
        $this->inscripcionService      = $inscripcionService;
        $this->inscripcionQueryService = $inscripcionQueryService;
        $this->postulanteService       = $postulanteService;
        $this->categoriaService        = $categoriaService;
        $this->bulkInscripcionService  = $bulkInscripcionService;
    }
 
    /**
     * Crear una inscripción
     */
    public function crear(StoreInscripcionRequest $request)
    {

        try {
        
            $all = $request->all();
            $resultado = $this->inscripcionService->crearInscripciones($all);  
            return response()->json(['data' => $resultado], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error en store Inscripcion', ['exception' => $e->getMessage(), 'traza' => $e->getTraceAsString()]);  
            return response()->json(['error' => $e->getMessage()], 500);  
        }
    }

    /**
     * Mostrar una inscripción específica
     */
    public function mostrar($id)
    {
        try {
            $ins = Inscripcion::with([
                'postulante.provincia.departamento',
                'nivelCompetencia.area',
                'nivelCompetencia.categoria',
                'colegio'
            ])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Inscripción no encontrada'], 404);
        }

        $grupo = Inscripcion::where('postulante_id', $ins->postulante_id)
            ->with(['nivelCompetencia.area', 'nivelCompetencia.categoria'])
            ->get();

        // Datos ya desencriptados gracias a los accessors en el modelo
        $result = [
            'id'                 => $ins->id,
            'nombres'            => $ins->postulante->nombres,
            'apellidos'          => $ins->postulante->apellidos,
            'ci'                 => $ins->postulante->ci,
            'departamento'       => $ins->postulante->provincia->departamento->nombre,
            'provincia'          => $ins->postulante->provincia->nombre,
            'colegio'            => $ins->colegio->nombre,
            'inscripciones'=> $grupo->map(function($inscripcion) {
                return [
                    'nivel_competencia' => $inscripcion->nivelCompetencia->area->nombre . ' - ' . $inscripcion->nivelCompetencia->categoria->nombre,
                    'id' => $inscripcion->id,
                    'estado' => $inscripcion->estado
                ];
            })
        ];

        return response()->json(['data' => $result]);
    }

    /**
     * Filtrar inscripciones por estado
     */
    public function obtenerPorEstado($olimpiadaId, $estado)
    {
        if (!in_array($estado, ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])) {
            return response()->json(['error' => 'Estado no válido'], 400);
        }

        $inscripciones = Inscripcion::with([
            'postulante',
            'nivelCompetencia.area',
            'nivelCompetencia.categoria'
        ])
        ->whereHas('nivelCompetencia', function($query) use ($olimpiadaId) {
            $query->where('olimpiada_id', $olimpiadaId);
        })
        ->where('estado', $estado)
        ->get()
        ->groupBy('postulante_id');

        $formattedData = $inscripciones->map(function ($grupo) use ($estado) {
            $firstInscripcion = $grupo->first();

            // Solo obtener los niveles de competencia que coincidan con el estado solicitado
            $nivelesCompetencia = $grupo
                ->where('estado', $estado)
                ->map(function($inscripcion) {
                    return $inscripcion->nivelCompetencia->area->nombre . ' - ' . $inscripcion->nivelCompetencia->categoria->nombre;
                })
                ->values()
                ->toArray();

            // Los datos ya están desencriptados gracias a los accessors
            return [
                'postulante_id' => $firstInscripcion->postulante_id,
                'nombres' => $firstInscripcion->postulante->nombres,
                'apellidos' => $firstInscripcion->postulante->apellidos,
                'ci' => $firstInscripcion->postulante->ci,
                'nivel_competencia' => $nivelesCompetencia
            ];
        })->values();

        return response()->json([
            'count' => $formattedData->count(),
            'estado' => $estado,
            'data' => $formattedData
        ], 200);
    }


    /**
     * Actualizar estado de inscripcion
     */
    public function actualizarEstadoInscripcion(Request $request, $ci)
    {
        $validator = Validator::make($request->all(), [
            'estado_nuevo' => 'required|in:Preinscrito,Pago Pendiente,Inscripcion Completa',
            'id_area' => 'required|exists:areas,id',
            'id_categoria' => 'required|exists:categorias,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Buscar postulante por CI desencriptado
            $postulante = null;
            $allPostulantes = Postulante::all();
            foreach ($allPostulantes as $p) {
                if ($p->ci === $ci) {
                    $postulante = $p;
                    break;
                }
            }

            if (!$postulante) {
                return response()->json(['error' => 'Postulante no encontrado con el CI proporcionado'], 404);
            }

            $inscripcion = Inscripcion::where('postulante_id', $postulante->id)
            ->whereHas('nivelCompetencia', function($query) use ($request) {
                $query->where('area_id', $request->id_area)
                      ->where('categoria_id', $request->id_categoria);
            })
            ->with(['nivelCompetencia.area', 'nivelCompetencia.categoria'])
            ->firstOrFail();

            $inscripcion->estado = $request->estado_nuevo;
            $inscripcion->save();

            return response()->json([
                'estado' => $inscripcion->estado,
                'nivel_competencia' => $inscripcion->nivelCompetencia->area->nombre . ' - ' . $inscripcion->nivelCompetencia->categoria->nombre
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'No se encontró la inscripción para el postulante con CI: ' . $ci .
                          ' en el área y categoría especificadas'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el estado de la inscripción'
            ], 500);
        }
    }


    /**
     * Contar inscritos por área
     */
    public function contarPorArea($areaId)
    {
        $data = $this->inscripcionQueryService->contarPorArea($areaId);
        return response()->json($data, 200);
    }

    /**
     * Contar inscritos por categoría
     */
    public function contarPorCategoria($categoriaId)
    {
        $data = $this->inscripcionQueryService->contarPorCategoria($categoriaId);
        return response()->json($data, 200);
    }

    /**
     * Listar inscritos en un área específica
     */
    public function obtenerInscripcionesPorArea($areaId)
    {
        $validator = Validator::make(['area_id' => $areaId], ['area_id' => 'required|exists:areas,id']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Área no válida'], 400);
        }

        $inscripciones = Inscripcion::with([
                'postulante.provincia.departamento',
                'nivelCompetencia.categoria',
                'colegio'
            ])
            ->whereHas('nivelCompetencia', fn($q) => $q->where('area_id', $areaId))
            ->get()
            ->map(fn($ins) => [
                'id'        => $ins->id,
                'postulante'=> [
                    'nombres'    => $ins->postulante->nombres,
                    'apellidos'  => $ins->postulante->apellidos,
                    'ci'         => $ins->postulante->ci,
                    'departamento'=> $ins->postulante->provincia->departamento->abreviatura,
                    'provincia'   => $ins->postulante->provincia->nombre,
                    'colegio'     => $ins->colegio->nombre,
                    'categoria'   => $ins->nivelCompetencia->categoria->nombre,
                    'estado'      => $ins->estado
                ]
            ]);

        return response()->json([
            'area'  => Area::find($areaId)->nombre,
            'total' => $inscripciones->count(),
            'data'  => $inscripciones
        ], 200);
    }

    /**
     * Listar inscritos en una categoría específica
     */
    public function obtenerInscripcionesPorCategoria($categoriaId)
    {
        $validator = Validator::make(['categoria_id' => $categoriaId], ['categoria_id' => 'required|exists:categorias,id']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Categoría no válida'], 400);
        }

        $data = $this->categoriaService->obtenerInscripcionesPorCategoria($categoriaId);

        return response()->json($data, 200);
    }

    /**
     * Formatear inscripciones agrupadas por postulante
     */
    private function formatearInscripcionesAgrupadas($inscripciones)
    {
        return $inscripciones->map(function ($group) {
            $firstInscripcion = $group->first();
            return [
                'id'                 => $firstInscripcion->postulante_id,
                'nombres'            => $firstInscripcion->postulante->nombres,
                'apellidos'          => $firstInscripcion->postulante->apellidos,
                'ci'                 => $firstInscripcion->postulante->ci,
                'departamento'       => $firstInscripcion->postulante->provincia->departamento->abreviatura,
                'provincia'          => $firstInscripcion->postulante->provincia->nombre,
                'colegio'            => $firstInscripcion->colegio->nombre,
                'areas'              => $group->pluck('nivelCompetencia.area.nombre')->unique()->values(),
                'categorias'         => $group->pluck('nivelCompetencia.categoria.nombre')->unique()->values(),
                'email'              => $firstInscripcion->email,
                'telefono'           => $firstInscripcion->telefono,
                'estado'             => $firstInscripcion->estado,
                'fecha_inscripcion'  => $firstInscripcion->fecha_inscripcion
            ];
        });
    }

    private function procesarPostulante($data, $lista, $indice)
    {
        return $this->postulanteService->procesarPostulante($data, $lista, $indice);
    }
    /**
     * Devuelve las inscripciones detalladas de una Olimpiada
     *
     * @param  int  $olimpiada_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerInscripcionesDetalladasPorOlimpiada(int $olimpiada_id)
    {
        try {
            // 1) Verificar existencia de la olimpiada
            $olimpiada = Olimpiada::find($olimpiada_id);
            if (! $olimpiada) {
                return response()->json(['mensaje' => 'Olimpiada no encontrada'], 404);
            }

            // 2) Traer inscripciones con relaciones necesarias
            $inscripciones = Inscripcion::with([
                    'postulante.provincia.departamento',
                    'nivelCompetencia.area',
                    'nivelCompetencia.categoria',
                    'colegio',
                    'responsable'
                ])
                ->whereHas('nivelCompetencia', fn($q) => $q->where('olimpiada_id', $olimpiada_id))
                ->get();

            if ($inscripciones->isEmpty()) {
                return response()->json([], 200);
            }

            // 3) Agrupar por postulante y formatear cada grupo
            $resultado = $inscripciones
                ->groupBy('postulante_id')
                ->map(function ($grupo) {
                    $ins  = $grupo->first();
                    $post = $ins->postulante;
                    $prov = $post->provincia;
                    $dep  = $prov->departamento;
                    $resp = $ins->responsable;

                    // Formatear grado
                    $curso   = $post->curso;
                    $ordinal = ['1ro','2do','3ro','4to','5to','6to'];
                    if ($curso >= 1 && $curso <= 6) {
                        $grado = "{$ordinal[$curso - 1]} primaria";
                    } elseif ($curso >= 7 && $curso <= 12) {
                        $grado = "{$ordinal[$curso - 7]} secundaria";
                    } else {
                        $grado = null;
                    }

                    // Formatear inscripciones con sus estados
                    $inscripciones = $grupo->map(function($inscripcion) {
                        return [
                            'nivel_competencia' => $inscripcion->nivelCompetencia->area->nombre . ' - ' . $inscripcion->nivelCompetencia->categoria->nombre,
                            'estado' => $inscripcion->estado
                        ];
                    })->values();

                    return [
                        'nombres'             => $post->nombres,
                        'apellidos'           => $post->apellidos,
                        'ci'                  => $post->ci,
                        'fecha_nacimiento'    => optional($post->fecha_nacimiento)->toDateString(),
                        'departamento'        => $dep->nombre,
                        'grado'               => $grado,
                        'nombre_responsable'  => $resp->nombre_completo,
                        'inscripciones'       => $inscripciones
                    ];
                })
                ->values();

            // 4) Devolver JSON con estatus 200 y total de postulantes
            return response()->json([
                'total_postulantes' => $resultado->count(),
                'postulantes' => $resultado
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener inscripciones',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function crearMasivo(BulkInscripcionRequest $request)
    {
        $payload = $request->all();

        // Convertir cada fecha a formato "d-m-Y"
        foreach ($payload['listaPostulantes'] as &$p) {
            // Si la fecha viene en formato JavaScript (Sat Jul 19 2008...)
            if (strpos($p['fecha_nacimiento'], 'GMT') !== false) {
                $date = new \DateTime($p['fecha_nacimiento']);
                $p['fecha_nacimiento'] = $date->format('d-m-Y');
            }
            if (!isset($p['contactos'])) {
            $p['contactos'] = [[
                'telefono_contacto' => $p['telefono_contacto'] ?? null,
                'tipo_contacto_telefono' => $p['tipo_contacto_telefono'] ?? null,
                'email_contacto' => $p['email_contacto'] ?? null,
                'tipo_contacto_email' => $p['tipo_contacto_email'] ?? null
                ]];
            }
            // Si ya viene en otro formato, asumimos que es válido y lo dejamos como está
        }
        unset($p); // rompe la referencia

        try {
            $resultado = $this->bulkInscripcionService->crearMasivo($payload);

            // Si hay errores de validación, retornar con status 422
            if (isset($resultado['errores'])) {
                return response()->json($resultado, 422);
            }

            // Si todo salió bien, retornar con status 201
            return response()->json($resultado, 201);

        } catch (\Exception $e) {
            Log::error('Error en crearMasivo', [
                'mensaje' => $e->getMessage(),
                'traza'   => $e->getTraceAsString()
            ]);
            return response()->json([
                'mensaje' => 'Error interno al procesar la inscripción masiva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function validarSolicitudMasiva(Request $request): array
    {
        return (new BulkInscripcionRequest())->rules();
    }

    protected function obtenerOLista(array $data): Lista
    {
        return $this->bulkInscripcionService->obtenerOLista($data);
    }

    /**
     * Muestra el historial de participación por CI (postulante o responsable)
     */
    public function mostrarPorCi($ci)
    {
        try {
            // Primero verificamos si existe como responsable
            $responsable = null;
            $allResponsables = Responsable::all();
            foreach ($allResponsables as $resp) {
                if ($resp->ci === $ci) {
                    $responsable = $resp;
                    break;
                }
            }
            
            if ($responsable) {
                // Obtener todas las listas del responsable
                $listas = Lista::with('olimpiada')
                    ->where('responsable_id', $responsable->id)
                    ->get()
                    ->groupBy('olimpiada.id');

                $participaciones = [];
                foreach ($listas as $olimpiadaId => $listasGrupo) {
                    $olimpiada = $listasGrupo->first()->olimpiada;

                    $participaciones[] = [
                        'olimpiada' => $olimpiada->nombre,
                        'listas' => $listasGrupo->map(function($lista) {
                            return [
                                'codigo_lista' => $lista->codigo_lista,
                                'cantidad_inscritos' => $lista->inscripciones()->count(),
                                'estado' => $lista->estado,
                                'fecha_creacion' => $lista->created_at->format('d-m-Y')
                            ];
                        })->values()
                    ];
                }

                return response()->json([
                    'responsable' => [
                        'ci' => $responsable->ci,
                        'nombre' => $responsable->nombre_completo,
                        'correo' => $responsable->email,
                        'telefono' => $responsable->telefono,
                        'participaciones' => $participaciones
                    ]
                ], 200);
            }

            // Si no es responsable, verificamos si es postulante
            $postulante = null;
            $allPostulantes = Postulante::all();
            foreach ($allPostulantes as $p) {
                if ($p->ci === $ci) {
                    $postulante = $p;
                    break;
                }
            }

            if ($postulante) {
                $inscripciones = Inscripcion::with([
                    'nivelCompetencia.area',
                    'nivelCompetencia.categoria',
                    'nivelCompetencia.olimpiada'
                ])
                ->where('postulante_id', $postulante->id)
                ->get()
                ->groupBy('nivelCompetencia.olimpiada.id');

                $participaciones = [];
                foreach ($inscripciones as $olimpiadaId => $inscripcionesGrupo) {
                    $olimpiada = $inscripcionesGrupo->first()->nivelCompetencia->olimpiada;

                    $participaciones[] = [
                        'olimpiada' => $olimpiada->nombre,
                        'inscripciones' => $inscripcionesGrupo->map(function($inscripcion) {
                            return [
                                'nivel_competencia' => $inscripcion->nivelCompetencia->area->nombre . ' - ' .
                                                     $inscripcion->nivelCompetencia->categoria->nombre,
                                'estado' => $inscripcion->estado
                            ];
                        })->values()
                    ];
                }

                return response()->json([
                    'postulante' => [
                        'nombres' => $postulante->nombres,
                        'apellidos' => $postulante->apellidos,
                        'ci' => $postulante->ci,
                        'departamento' => $postulante->provincia->departamento->abreviatura,
                        'participaciones' => $participaciones
                    ]
                ], 200);
            }

            return response()->json([
                'error' => 'No se encontró ningún postulante o responsable con el CI proporcionado'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los datos: ' . $e->getMessage()
            ], 500);
        }
    }



    public function mostrarDetallesPostulantePorCi($ci, $olimpiadaId)
    {
        try {
            // 1) Verificar que exista la olimpiada (para obtener su nombre, aunque no se muestra en la respuesta)
            Olimpiada::findOrFail($olimpiadaId);

            // 2) Buscar al postulante con CI desencriptado
            $postulante = null;
            $allPostulantes = Postulante::with([
                'provincia.departamento',
                'inscripciones.nivelCompetencia.area',
                'inscripciones.nivelCompetencia.categoria',
                'inscripciones.nivelCompetencia.olimpiada',
                'inscripciones.colegio'
            ])->get();
            
            foreach ($allPostulantes as $p) {
                if ($p->ci === $ci) {
                    $postulante = $p;
                    break;
                }
            }

            if (!$postulante) {
                return response()->json([
                    'error' => 'No se encontró ningún postulante con el CI proporcionado'
                ], 404);
            }

            // 3) Filtrar en memoria solo las inscripciones de esta olimpiada
            $inscripcionesFiltradas = $postulante->inscripciones
                ->filter(function($ins) use ($olimpiadaId) {
                    return $ins->nivelCompetencia->olimpiada_id === (int) $olimpiadaId;
                })
                ->values();

            if ($inscripcionesFiltradas->isEmpty()) {
                return response()->json([
                    'error' => 'Este postulante no tiene inscripciones en la olimpiada indicada'
                ], 404);
            }

            // 4) Obtener la última inscripción filtrada para saber el colegio
            $ultimaInscripcion = $inscripcionesFiltradas->last();
            $colegio = $ultimaInscripcion->colegio;

            // 5) Formatear el array de inscripciones
            $inscripcionesFormateadas = $inscripcionesFiltradas->map(function($ins) {
                return [
                    'nivel_competencia' => 
                        $ins->nivelCompetencia->area->nombre
                        . ' - ' .
                        $ins->nivelCompetencia->categoria->nombre,
                    'id_area'      => $ins->nivelCompetencia->area->id,
                    'id_categoria' => $ins->nivelCompetencia->categoria->id,
                    'estado'       => $ins->estado,
                ];
            })->values();

            // 6) Armar la respuesta con la estructura solicitada (datos ya desencriptados por los accessors)
            $data = [
                'ci'                => $postulante->ci,
                'nombres'           => $postulante->nombres,
                'apellidos'         => $postulante->apellidos,
                'fecha_nacimiento'  => $postulante->fecha_nacimiento
                                          ? $postulante->fecha_nacimiento->format('d-m-Y')
                                          : null,
                'email'             => $postulante->email,
                'departamento'      => $postulante->provincia->departamento->nombre,
                'id_departamento'   => $postulante->provincia->departamento->id,
                'provincia'         => $postulante->provincia->nombre,
                'id_provincia'      => $postulante->provincia->id,
                'colegio'           => $colegio ? $colegio->nombre : null,
                'id_colegio'        => $colegio ? $colegio->id : null,
                'curso'             => $postulante->curso,
                'inscripciones'     => $inscripcionesFormateadas,
            ];

            return response()->json([
                'postulante' => $data
            ], 200);

        } catch (ModelNotFoundException $e) {
            // Si falla Olimpiada::findOrFail($olimpiadaId) o no hay postulante / inscripciones
            return response()->json([
                'error' => 'Olimpiada no encontrada'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los datos: ' . $e->getMessage()
            ], 500);
        }
    }


    public function obtenerReporteDeInscripciones($olimpiada_id)
    {
        try {
            $olimpiada = Olimpiada::find($olimpiada_id);
            if (!$olimpiada) {
                return response()->json(['message' => 'Olimpiada no encontrada'], 404);
            }

            $inscripciones = Inscripcion::with([
                'postulante.provincia.departamento',
                'nivelCompetencia.area',
                'nivelCompetencia.categoria',
                'colegio',
                'responsable'
            ])
            ->whereHas('nivelCompetencia', function ($query) use ($olimpiada_id) {
                $query->where('olimpiada_id', $olimpiada_id);
            })
            ->get();

            if ($inscripciones->isEmpty()) {

                return response()->json([], 200);
            }

            $resultado = $inscripciones->map(function ($inscripcion) {
                $postulante = $inscripcion->postulante;
                $nivelCompetencia = $inscripcion->nivelCompetencia;
                $colegio = $inscripcion->colegio;
                $responsable = $inscripcion->responsable;

                $provincia = $postulante ? $postulante->provincia : null;
                $departamento = $provincia ? $provincia->departamento : null;
                $area = $nivelCompetencia ? $nivelCompetencia->area : null;
                $categoria = $nivelCompetencia ? $nivelCompetencia->categoria : null;

                return [
                    'nombre'        => $postulante ? $postulante->nombres : null,
                    'apellidos'     => $postulante ? $postulante->apellidos : null,
                    'ci'            => $postulante ? $postulante->ci : null,
                    'fechaNac'      => $postulante && $postulante->fecha_nacimiento ? Carbon::parse($postulante->fecha_nacimiento)->toDateString() : null,
                    'area'          => $area ? $area->nombre : null,
                    'categoria'     => $categoria ? $categoria->nombre : null,
                    'departamento'  => $departamento ? $departamento->nombre : null,
                    'provincia'     => $provincia ? $provincia->nombre : null,
                    'colegio'       => $colegio ? $colegio->nombre : null,
                    'grado'         => $postulante && $postulante->curso ? $postulante->curso . '°' : null,
                    'responsable'   => $responsable ? $responsable->nombre_completo : null,
                    'responsableCi' => $responsable ? $responsable->ci : null,
                    'estado'        => $inscripcion->estado,
                ];
            });

            return response()->json($resultado);

        } catch (\Exception $e) {
            Log::error('Error al obtener inscripciones detalladas: ' . $e->getMessage());
            return response()->json(['message' => 'Error al procesar la solicitud', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar olimpiadas por CI
     */
    public function mostrarPorCiOlimpiadas($ci)
    {
        try {
            // Buscar postulante por CI desencriptado
            $postulante = null;
            $allPostulantes = Postulante::all();
            foreach ($allPostulantes as $p) {
                if ($p->ci === $ci) {
                    $postulante = $p;
                    break;
                }
            }

            if ($postulante) {
                $olimpiadas = Olimpiada::whereHas('nivelesCompetencia.inscripciones', function($query) use ($postulante) {
                    $query->where('postulante_id', $postulante->id);
                })->get();
                
                return response()->json([
                    'postulante' => [
                        'ci' => $ci,
                        'nombres' => $postulante->nombres,
                        'apellidos' => $postulante->apellidos,
                        'olimpiadas' => $olimpiadas->map(function($olimpiada) {
                            return [
                                'id' => $olimpiada->id,
                                'nombre' => $olimpiada->nombre,
                                'año' => $olimpiada->anio
                            ];
                        })->toArray()
                    ]
                ], 200);
            } 
            
            // Si no es postulante, buscamos como responsable
            $responsable = null;
            $allResponsables = Responsable::all();
            foreach ($allResponsables as $resp) {
                if ($resp->ci === $ci) {
                    $responsable = $resp;
                    break;
                }
            }
            
            if ($responsable) {
                $olimpiadas = Olimpiada::whereHas('listas', function($query) use ($responsable) {
                    $query->where('responsable_id', $responsable->id);
                })->get();
                
                return response()->json([
                    'responsable' => [
                        'ci' => $ci,
                        'nombre' => $responsable->nombre_completo,
                        'olimpiadas' => $olimpiadas->map(function($olimpiada) {
                            return [
                                'id' => $olimpiada->id,
                                'nombre' => $olimpiada->nombre,
                                'año' => $olimpiada->anio
                            ];
                        })->toArray()
                    ]
                ], 200);
            }
            
            return response()->json([
                'error' => 'No se encontró ningún postulante ni responsable con el CI proporcionado'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los datos: ' . $e->getMessage()
            ], 500);
        }
    }
}
