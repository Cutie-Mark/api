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
    public function store(StoreInscripcionRequest $request)
    {

        try {
            // 2) No es necesario volver a “reformatear” la fecha, pues ya estaba en Y-m-d.
            //    Antes intentábamos parsear d-m-Y y daba error; ahora usamos la fecha tal cual llega.
            $all = $request->all(); 
            // 3) Llamar al service CORRECTO: crearInscripciones()
            $resultado = $this->inscripcionService->crearInscripciones($all);   // → CORRECCIÓN: llamar al método que sí existe

            return response()->json(['data' => $resultado], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error en store Inscripcion', ['exception' => $e->getMessage(), 'traza' => $e->getTraceAsString()]);  // → CORRECCIÓN: añadir traza para debugging
            return response()->json(['error' => $e->getMessage()], 500);  // → CORRECCIÓN: devolver el mensaje real para ayudar a depurar (puedes omitir la traza en producción)
        }
    }

    /**
     * Mostrar una inscripción específica
     */
    public function show($id)
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
                    'estado' => $inscripcion->estado
                ];
            })->values(),
            'email'              => $ins->email,
            'telefono'           => $ins->telefono,
            'fecha_inscripcion'  => $ins->fecha_inscripcion,
        ];

        return response()->json(['data' => $result], 200);
    }

    /**
     * Filtrar inscripciones por estado
     */
    public function getByEstado($olimpiadaId, $estado)
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
    public function updateEstadoInscripcion(Request $request, $ci)
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
            $inscripcion = Inscripcion::whereHas('postulante', function($query) use ($ci) {
                $query->where('ci', $ci);
            })
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
    public function countByArea($areaId)
    {
        $data = $this->inscripcionQueryService->countByArea($areaId);
        return response()->json($data, 200);
    }

    /**
     * Contar inscritos por categoría
     */
    public function countByCategoria($categoriaId)
    {
        $data = $this->inscripcionQueryService->countByCategoria($categoriaId);
        return response()->json($data, 200);
    }

    /**
     * Listar inscritos en un área específica
     */
    public function getInscripcionesByArea($areaId)
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
    public function getInscripcionesByCategoria($categoriaId)
    {
        $validator = Validator::make(['categoria_id' => $categoriaId], ['categoria_id' => 'required|exists:categorias,id']);
        if ($validator->fails()) {
            return response()->json(['error' => 'Categoría no válida'], 400);
        }

        $data = $this->categoriaService->getInscripcionesByCategoria($categoriaId);

        return response()->json($data, 200);
    }

    /**
     * Formatear inscripciones agrupadas por postulante
     */
    private function formatGroupedInscripciones($inscripciones)
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
    public function getInscripcionesDetalladasPorOlimpiada(int $olimpiada_id)
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

    public function storeBulk(BulkInscripcionRequest $request)
    {
        $payload = $request->all();
        // Convertir cada fecha a "Y-m-d" antes de pasar al Service
        foreach ($payload['listaPostulantes'] as &$p) {
            $p['fecha_nacimiento'] = \Carbon\Carbon::createFromFormat('d-m-Y', $p['fecha_nacimiento'])
                                        ->format('Y-m-d');
        }
        unset($p); // rompe la referencia

        // ────────────────────────────────────────────────────────────────
        // 5) Llamar al Service que crea todo en transacción
        try {
            [ $exitosos, $errores ] = $this->bulkInscripcionService->storeBulk($payload);
            return response()->json([
                'exitosos' => $exitosos,
                'errores'   => $errores
            ], 200);

        } catch (\Exception $e) {
            // (durante la depuración, puedes devolver $e->getMessage() para ver el detalle)
            Log::error('Error en storeBulk', [
                'mensaje' => $e->getMessage(),
                'traza'   => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error interno al procesar la inscripción masiva'
            ], 500);
        }
    }
    
    protected function validateBulkRequest(Request $request): array
    {
        return (new BulkInscripcionRequest())->rules();
    }

    protected function obtenerOLista(array $data): Lista
    {
        return $this->bulkInscripcionService->obtenerOLista($data);
    }
}
