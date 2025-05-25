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
    public function store(Request $request)
    {
        if ($request->has('codigo_lista') && $request->input('codigo_lista') === '') {
        $request->merge(['codigo_lista' => null]);
    }
        // 1) Validar entrada
        $validator = Validator::make($request->all(), [
            'nombres'                => ['required','string','max:255','regex:/^[^\d]+$/'],
            'apellidos'              => ['required','string','max:255','regex:/^[^\d]+$/'],
            'ci'                     => ['required','string','max:10','regex:/^\d{1,10}$/'],
            'fecha_nacimiento'       => 'required|date_format:Y-m-d',
            'correo_postulante'      => ['required','email:rfc'],
            'curso'                  => 'required|integer|between:1,12',
            'departamento'           => 'required|exists:departamentos,id',
            'provincia'              => 'required|exists:provincias,id',
            'niveles_competencia'                  => ['required', 'array', 'min:1', function($attribute, $value, $fail) {
                $olimpiada_id = request('olimpiada_id');
                if (!$olimpiada_id) {
                    return;
                }
                $olimpiada = \App\Models\Olimpiada::find($olimpiada_id);
                if (!$olimpiada) {
                    return;
                }
                
                if (count($value) > $olimpiada->limite_inscripciones) {
                    $fail("No puedes inscribirte en más de {$olimpiada->limite_inscripciones} niveles de competencia");
                }
            }],
            'niveles_competencia.*.id_area'        => 'required|exists:areas,id',
            'niveles_competencia.*.id_cat'         => 'required|exists:categorias,id',
            'email_contacto'         => ['required','email:rfc'],
            'tipo_contacto_email'    => 'required|integer|in:1,2,3',
            'telefono_contacto'      => ['required', 'regex:/^[0-9]{7,8}$/'],
            'tipo_contacto_telefono' => 'required|integer|in:1,2,3',
            'colegio'                => 'required|exists:colegios,id',
            'codigo_lista'           => 'required|string|exists:listas,codigo_lista'
        ], [
            'required'                => 'El campo :attribute es obligatorio',
            'exists'                 => 'El valor seleccionado en :attribute no es válido',
            'niveles_competencia.max'              => 'No puedes inscribirte en más de :max niveles de competencia',
            'between'                => 'El curso debe estar entre 1ro de primaria y 6to de secundaria',
            'nombres.regex'          => 'El campo nombres no debe contener numeros',
            'apellidos.regex'        => 'El campo apellidos no debe contener numeros',
            'ci.regex'               => 'CI no debe contener letras',
            'ci.max'                 => 'CI no debe tener más de 10 dígitos',
            'correo_postulante.email' => 'Tipo de correo inválido en campo correo postulante',
            'email_contacto.email'    => 'Tipo de correo inválido en campo email contacto',
            'tipo_contacto_email.in'  => 'Tipo de contacto inválido en email contacto',
            'tipo_contacto_telefono.in' => 'Tipo de contacto inválido en telefono contacto',
            'telefono_contacto.regex' => 'Teléfono incorrecto, debe tener entre 7 y 8 dígitos',
            'colegio.exists'          => 'Colegio no encontrado',
            'codigo_lista.exists'     => 'Codigo de lista invalido',
            'codigo_lista.required'   => 'Codigo de lista vacio'
        ])->setAttributeNames([
            'nombres'                => 'nombres',
            'apellidos'              => 'apellidos',
            'ci'                     => 'CI',
            'fecha_nacimiento'       => 'fecha de nacimiento',
            'correo_postulante'      => 'correo del postulante',
            'curso'                  => 'curso',
            'departamento'           => 'departamento',
            'provincia'              => 'provincia',
            'niveles_competencia'                  => 'niveles de competencia',
            'niveles_competencia.*.id_area'        => 'área',
            'niveles_competencia.*.id_cat'         => 'categoría',
            'email_contacto'         => 'correo de contacto',
            'tipo_contacto_email'    => 'tipo de contacto email',
            'telefono_contacto'      => 'teléfono de contacto',
            'tipo_contacto_telefono' => 'tipo de contacto teléfono',
            'colegio'                => 'colegio',
            'codigo_lista'           => 'código de lista'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

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
     * Listar todas las inscripciones agrupadas por postulante
     */
    public function index()
    {
        $inscripciones = Inscripcion::with([
            'postulante.provincia.departamento',
            'nivelCompetencia.area',
            'nivelCompetencia.categoria',
            'colegio'
        ])->get()->groupBy('postulante_id');

        $data = $this->formatGroupedInscripciones($inscripciones);

        return response()->json(['data' => $data], 200);
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
            'departamento'       => $ins->postulante->provincia->departamento->abreviatura,
            'provincia'          => $ins->postulante->provincia->nombre,
            'colegio'            => $ins->colegio->nombre,
            'areas'              => $grupo->pluck('nivelCompetencia.area.nombre')->unique()->values(),
            'categorias'         => $grupo->pluck('nivelCompetencia.categoria.nombre')->unique()->values(),
            'email'              => $ins->email,
            'telefono'           => $ins->telefono,
            'estado'             => $ins->estado,
            'fecha_inscripcion'  => $ins->fecha_inscripcion,
        ];

        return response()->json(['data' => $result], 200);
    }

    /**
     * Filtrar inscripciones por estado
     */
    public function getByEstado($estado)
    {
        if (!in_array($estado, ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])) {
            return response()->json(['error' => 'Estado no válido'], 400);
        }

        $formatted = $this->inscripcionQueryService->getByEstado($estado);

        return response()->json([
            'count' => $formatted->count(),
            'data'  => $formatted,
        ], 200);
    }


    /**
     * Actualizar estado de inscripcion
     */
    public function updateEstadoInscripcion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:Preinscrito,Pago Pendiente,Inscripcion Completa'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            $data = $this->inscripcionQueryService->updateEstadoInscripcion($id, $request->estado);
            return response()->json(['data' => $data], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Inscripción no encontrada'], 404);
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

                    // Niveles de competencia únicos
                    $niveles = $grupo
                        ->map(fn($i) => $i->nivelCompetencia->area->nombre . ' - ' . $i->nivelCompetencia->categoria->nombre)
                        ->unique()
                        ->values();

                    return [
                        'nombres'             => $post->nombres,
                        'apellidos'           => $post->apellidos,
                        'fecha_nacimiento'    => optional($post->fecha_nacimiento)->toDateString(),
                        'departamento'        => $dep->nombre,
                        'provincia'           => $prov->nombre,
                        'colegio'             => $ins->colegio->nombre,
                        'grado'               => $grado,
                        'nombre_responsable'  => $resp->nombre_completo,
                        'niveles_competencia' => $niveles,
                        'estado'              => $ins->estado,
                    ];
                })
                ->values();

            // 4) Devolver JSON con estatus 200
            return response()->json($resultado, 200);

        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Error al obtener inscripciones',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function storeBulk(Request $request)
    {
        // ────────────────────────────────────────────────────────────────
        // 1) Si 'codigo_lista' viene como cadena vacía, conviértelo a null
        //    para que "nullable|exists:listas,codigo_lista" no falle.
        if ($request->has('codigo_lista') && $request->input('codigo_lista') === '') {
            $request->merge(['codigo_lista' => null]);
        }

        // ────────────────────────────────────────────────────────────────
        // 2) Normalizar todas las fechas de 'listaPostulantes':
        //    si vienen en formato "YYYY-MM-DD", las convertimos a "DD-MM-YYYY"
        foreach ($request->input('listaPostulantes', []) as $i => $post) {
            $rawFecha = $post['fecha_nacimiento'] ?? null;

            // Si el string coincide con "YYYY-MM-DD"
            if (is_string($rawFecha) && preg_match('#^\d{4}-\d{2}-\d{2}$#', $rawFecha)) {
                $carbon = \Carbon\Carbon::createFromFormat('Y-m-d', $rawFecha);
                // Sobrescribimos ese campo para que quede en "DD-MM-YYYY"
                $request->merge([
                    "listaPostulantes.$i.fecha_nacimiento" => $carbon->format('d-m-Y')
                ]);
            }
            // Si ya venía en "DD-MM-YYYY", permanece igual y pasará la validación.
        }

        // ────────────────────────────────────────────────────────────────
        // 3) Validar todo el payload, incluyendo que cada 'fecha_nacimiento' esté en "d-m-Y"
        $validator = Validator::make($request->all(), [
            'ci'                                 => 'required|string|exists:responsables,ci',
            'olimpiada_id'                       => 'required|exists:olimpiadas,id',
            'codigo_lista'                       => 'nullable|string|exists:listas,codigo_lista',
            'listaPostulantes'                   => 'required|array|min:1',
            'listaPostulantes.*.nombres'         => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.apellidos'       => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.ci'              => ['required','string','max:10','regex:/^\d{1,10}$/'],
            'listaPostulantes.*.fecha_nacimiento'=> 'required|date_format:d-m-Y',
            'listaPostulantes.*.correo_postulante'=> ['required','email:rfc'],
            'listaPostulantes.*.email_contacto'  => ['required','email:rfc'],
            'listaPostulantes.*.tipo_contacto_email' => 'required|integer|in:1,2,3',
            'listaPostulantes.*.telefono_contacto'=> ['required', 'regex:/^[0-9]{7,8}$/'],
            'listaPostulantes.*.tipo_contacto_telefono' => 'required|integer|in:1,2,3',
            'listaPostulantes.*.idDepartamento'  => 'required|exists:departamentos,id',
            'listaPostulantes.*.idProvincia'     => 'required|exists:provincias,id',
            'listaPostulantes.*.idColegio'       => 'required|exists:colegios,id',
            'listaPostulantes.*.idCurso'         => 'required|integer|between:1,12',
            'listaPostulantes.*.inscripciones'   => ['required', 'array', 'min:1', function($attribute, $value, $fail) {
                $olimpiada_id = request('olimpiada_id');
                if (!$olimpiada_id) {
                    return;
                }
                $olimpiada = \App\Models\Olimpiada::find($olimpiada_id);
                if (!$olimpiada) {
                    return;
                }
                
                if (count($value) > $olimpiada->limite_inscripciones) {
                    $fail("No puedes inscribirte en más de {$olimpiada->limite_inscripciones} niveles de competencia");
                }
            }],
            'listaPostulantes.*.inscripciones.*.idArea'      => 'required|exists:areas,id',
            'listaPostulantes.*.inscripciones.*.idCategoria' => 'required|exists:categorias,id',
        ], [
            'listaPostulantes.*.nombres.regex' => 'El campo nombres no debe contener numeros',
            'listaPostulantes.*.apellidos.regex' => 'El campo apellidos no debe contener numeros',
            'listaPostulantes.*.ci.regex' => 'CI no debe contener letras',
            'listaPostulantes.*.ci.max' => 'CI no debe tener más de 10 dígitos',
            'listaPostulantes.*.correo_postulante.email' => 'Tipo de correo inválido en campo correo postulante',
            'listaPostulantes.*.email_contacto.email' => 'Tipo de correo inválido en campo email contacto',
            'listaPostulantes.*.tipo_contacto_email.in' => 'Tipo de contacto inválido en email contacto',
            'listaPostulantes.*.tipo_contacto_telefono.in' => 'Tipo de contacto inválido en telefono contacto',
            'listaPostulantes.*.telefono_contacto.regex' => 'Teléfono incorrecto, debe tener entre 7 y 8 dígitos',
            'listaPostulantes.*.idColegio.exists' => 'Colegio no encontrado',
            'codigo_lista.exists' => 'Codigo de lista invalido',
            'codigo_lista.required' => 'Codigo de lista invalido'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        // ────────────────────────────────────────────────────────────────
        // 4) Convertir cada fecha a "Y-m-d" antes de pasar al Service
        $payload = $request->all();
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
        return $request->validate([
            'ci'                                 => 'required|string|exists:responsables,ci',
            'olimpiada_id'                       => 'required|exists:olimpiadas,id',
            'codigo_lista'                       => 'nullable|string|exists:listas,codigo_lista',
            'listaPostulantes'                   => 'required|array|min:1',
            'listaPostulantes.*.nombres'             => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.apellidos'           => ['required','string','max:255','regex:/^[^\d]+$/'],
            'listaPostulantes.*.ci'                  => ['required','string','max:10','regex:/^\d{1,10}$/'],
            'listaPostulantes.*.fecha_nacimiento'    => 'required|date_format:d-m-Y',
            'listaPostulantes.*.correo_postulante'   => ['required','email:rfc'],
            'listaPostulantes.*.email_contacto'      => ['required','email:rfc'],
            'listaPostulantes.*.tipo_contacto_email' => 'required|integer|in:1,2,3',
            'listaPostulantes.*.telefono_contacto'    => ['required', 'regex:/^[0-9]{7,8}$/'],
            'listaPostulantes.*.tipo_contacto_telefono'=> 'required|integer|in:1,2,3',
            'listaPostulantes.*.idDepartamento'       => 'required|exists:departamentos,id',
            'listaPostulantes.*.idProvincia'          => 'required|exists:provincias,id',
            'listaPostulantes.*.idColegio'            => 'required|exists:colegios,id',
            'listaPostulantes.*.idCurso'              => 'required|integer|between:1,12',
            'listaPostulantes.*.inscripciones'        => ['required', 'array', 'min:1', function($attribute, $value, $fail) {
                $olimpiada_id = request('olimpiada_id');
                if (!$olimpiada_id) {
                    return;
                }
                $olimpiada = \App\Models\Olimpiada::find($olimpiada_id);
                if (!$olimpiada) {
                    return;
                }
                
                if (count($value) > $olimpiada->limite_inscripciones) {
                    $fail("No puedes inscribirte en más de {$olimpiada->limite_inscripciones} niveles de competencia");
                }
            }],
            'listaPostulantes.*.inscripciones.*.idArea'      => 'required|exists:areas,id',
            'listaPostulantes.*.inscripciones.*.idCategoria' => 'required|exists:categorias,id',
        ], [
            'listaPostulantes.*.nombres.regex' => 'El campo nombres no debe contener numeros',
            'listaPostulantes.*.apellidos.regex' => 'El campo apellidos no debe contener numeros',
            'listaPostulantes.*.ci.regex' => 'CI no debe contener letras',
            'listaPostulantes.*.ci.max' => 'CI no debe tener más de 10 dígitos',
            'listaPostulantes.*.correo_postulante.email' => 'Tipo de correo inválido en campo correo postulante',
            'listaPostulantes.*.email_contacto.email' => 'Tipo de correo inválido en campo email contacto',
            'listaPostulantes.*.tipo_contacto_email.in' => 'Tipo de contacto inválido en email contacto',
            'listaPostulantes.*.tipo_contacto_telefono.in' => 'Tipo de contacto inválido en telefono contacto',
            'listaPostulantes.*.telefono_contacto.regex' => 'Teléfono incorrecto, debe tener entre 7 y 8 dígitos',
            'listaPostulantes.*.idColegio.exists' => 'Colegio no encontrado',
            'codigo_lista.exists' => 'Codigo de lista invalido',
            'codigo_lista.required' => 'Codigo de lista invalido'
        ]);
    }

    protected function obtenerOLista(array $data): Lista
    {
        return $this->bulkInscripcionService->obtenerOLista($data);
    }
}
