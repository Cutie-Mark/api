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
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Olimpiada;
use Illuminate\Support\Facades\Log;
class InscripcionController extends Controller
{
    /**
     * Crear una inscripción
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres'                => 'required|string|max:255',
            'apellidos'              => 'required|string|max:255',
            'ci'                     => 'required|string|max:10',
            'fecha_nacimiento'       => 'required|date',
            'correo_postulante'      => 'required|email',
            'curso'                  => 'required|integer|between:1,12',
            'departamento'           => 'required|exists:departamentos,id',
            'provincia'              => 'required|exists:provincias,id',
            'areas'                  => 'required|array|min:1|max:2',
            'areas.*.id_area'        => 'required|exists:areas,id',
            'areas.*.id_cat'         => 'required|exists:categorias,id',
            'email_contacto'         => 'required|email',
            'tipo_contacto_email'    => 'required|in:1,2,3',
            'telefono_contacto'      => 'required|string|max:8',
            'tipo_contacto_telefono' => 'required|in:1,2,3',
            'colegio'                => 'required|exists:colegios,id',
            'codigo_lista'           => 'required|string|exists:listas,codigo_lista'
        ], [
            'required'  => 'El campo :attribute es obligatorio',
            'exists'    => 'El valor seleccionado en :attribute no es válido',
            'areas.max' => 'No puedes inscribirte en más de :max áreas',
            'between'   => 'El curso debe estar entre 1ro de primaria y 6to de secundaria'
        ])->setAttributeNames([
            'nombres'                => 'nombres',
            'apellidos'              => 'apellidos',
            'ci'                     => 'CI',
            'fecha_nacimiento'       => 'fecha de nacimiento',
            'correo_postulante'      => 'correo del postulante',
            'curso'                  => 'curso',
            'departamento'           => 'departamento',
            'provincia'              => 'provincia',
            'areas'                  => 'áreas de inscripción',
            'areas.*.id_area'        => 'área',
            'areas.*.id_cat'         => 'categoría',
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

        return DB::transaction(function () use ($request) {
            // Crear o actualizar postulante
            $postulante = Postulante::updateOrCreate(
                ['ci' => $request->ci],
                [
                    'nombres'          => ucwords(strtolower($request->nombres)),
                    'apellidos'        => ucwords(strtolower($request->apellidos)),
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'email'            => $request->correo_postulante,
                    'curso'            => $request->curso,
                    'provincia_id'     => $request->provincia
                ]
            );

            // Obtener lista y olimpiada
            $lista = Lista::where('codigo_lista', $request->codigo_lista)->firstOrFail();
            $olimpiadaId = $lista->olimpiada_id;

            // Contar inscripciones existentes de esta olimpiada
            $insCount = Inscripcion::where('postulante_id', $postulante->id)
                ->whereHas('nivelCompetencia', fn($q) => $q->where('olimpiada_id', $olimpiadaId))
                ->count();

            // Validar que no supere 2 inscripciones
            if ($insCount + count($request->areas) > 2) {
                return response()->json([
                    'error' => 'Un estudiante no puede tener más de 2 inscripciones en la misma olimpiada'
                ], 400);
            }

            // Crear inscripciones por cada área
            foreach ($request->areas as $areaInput) {
                // Verificar duplicados por área o categoría
                $duplicate = Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', fn($q) =>
                        $q->where('olimpiada_id', $olimpiadaId)
                          ->where(function($q2) use ($areaInput) {
                              $q2->where('area_id', $areaInput['id_area'])
                                 ->orWhere('categoria_id', $areaInput['id_cat']);
                          })
                    )
                    ->exists();

                if ($duplicate) {
                    return response()->json([
                        'error' => 'El postulante ya está inscrito en esa área o categoría'
                    ], 400);
                }

                $nivel = NivelCompetencia::where('area_id', $areaInput['id_area'])
                    ->where('categoria_id', $areaInput['id_cat'])
                    ->where('olimpiada_id', $olimpiadaId)
                    ->first();

                if (! $nivel) {
                    return response()->json(['error' => 'La combinación área-categoría no es válida para la olimpiada seleccionada'], 400);
                }

                Inscripcion::create([
                    'postulante_id'        => $postulante->id,
                    'responsable_id'       => $lista->responsable_id,
                    'nivel_competencia_id' => $nivel->id,
                    'colegio_id'           => $request->colegio,
                    'orden_pago_id'        => null,
                    'lista_id'             => $lista->id,
                    'email'                => $request->email_contacto,
                    'tipo_contacto_email'  => $request->tipo_contacto_email,
                    'telefono'             => $request->telefono_contacto,
                    'tipo_contacto_telefono'=> $request->tipo_contacto_telefono,
                    'estado'               => 'Preinscrito'
                ]);
            }

            return response()->json(['mensaje' => 'Inscripción(es) creada(s) exitosamente'], 201);
        });
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
        if (! in_array($estado, ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])) {
            return response()->json(['error' => 'Estado no válido'], 400);
        }

        // Eager‐load las relaciones correctas
        $inscripciones = Inscripcion::with([
                'postulante:id,nombres,apellidos,ci',
                'nivelCompetencia.area:id,nombre',
                'nivelCompetencia.categoria:id,nombre',
            ])
            ->where('estado', $estado)
            ->get()
            ->groupBy('postulante_id');

        // Dar formato agrupado
        $formatted = $inscripciones->map(function($grupo) {
            $primera = $grupo->first();
            return [
                'postulante_id' => $primera->postulante_id,
                'nombres'       => $primera->postulante->nombres,
                'apellidos'     => $primera->postulante->apellidos,
                'ci'            => $primera->postulante->ci,
                'areas'         => $grupo->pluck('nivelCompetencia.area.nombre')->unique()->values(),
                'categorias'    => $grupo->pluck('nivelCompetencia.categoria.nombre')->unique()->values(),
                'estado'        => $primera->estado,
            ];
        })->values();

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
        // Validar nuevo estado
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:Preinscrito,Pago Pendiente,Inscripcion Completa'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            $inscripcion = Inscripcion::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Inscripción no encontrada'
            ], 404);
        }

        $inscripcion->estado = $request->estado;
        $inscripcion->save();

        return response()->json([
            'data' => [
                'id_inscripcion'    => $inscripcion->id,
                'estado_actualizado'=> $inscripcion->estado
            ]
        ], 200);
    }


    /**
     * Contar inscritos por área
     */
    public function countByArea($areaId)
    {
        $total = Inscripcion::whereHas('nivelCompetencia', fn($q) => $q->where('area_id', $areaId))
            ->distinct('postulante_id')->count('postulante_id');

        $nombre = Area::find($areaId)->nombre ?? 'Área no encontrada';
        return response()->json(['area_id' => $areaId, 'area_nombre' => $nombre, 'total' => $total], 200);
    }

    /**
     * Contar inscritos por categoría
     */
    public function countByCategoria($categoriaId)
    {
        $total = Inscripcion::whereHas('nivelCompetencia', fn($q) => $q->where('categoria_id', $categoriaId))
            ->distinct('postulante_id')->count('postulante_id');

        $nombre = Categoria::find($categoriaId)->nombre ?? 'Categoría no encontrada';
        return response()->json(['categoria_id' => $categoriaId, 'categoria_nombre' => $nombre, 'total' => $total], 200);
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

        $inscripciones = Inscripcion::with([
                'postulante.provincia.departamento',
                'nivelCompetencia.area',
                'colegio'
            ])
            ->whereHas('nivelCompetencia', fn($q) => $q->where('categoria_id', $categoriaId))
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
                    'area'        => $ins->nivelCompetencia->area->nombre,
                    'estado'      => $ins->estado
                ]
            ]);

        return response()->json([
            'categoria' => Categoria::find($categoriaId)->nombre,
            'total'     => $inscripciones->count(),
            'data'      => $inscripciones
        ], 200);
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
        return DB::transaction(function () use ($data, $lista, $indice) {
            // 1. Mapeo y transformación de datos
            $payload = $this->mapearDatosExcel($data, $lista);

            // 2. Validación
            $validator = Validator::make($payload, $this->getValidationRules(), $this->getCustomMessages());
            $validator->setAttributeNames($this->getAttributeNames());

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $provincia = Provincia::find($payload['provincia']);
            if (!$provincia || $provincia->departamento_id != $payload['departamento']) {
                throw new \Exception('La provincia no pertenece al departamento seleccionado');
            }

            // 3. Crear/Actualizar Postulante
            $postulante = Postulante::updateOrCreate(
                ['ci' => $payload['ci']],
                [
                    'nombres' => ucwords(strtolower($payload['nombres'])),
                    'apellidos' => ucwords(strtolower($payload['apellidos'])),
                    'fecha_nacimiento' => $payload['fecha_nacimiento'],
                    'email' => $payload['correo_postulante'],
                    'curso' => $payload['curso'],
                    'provincia_id' => $payload['provincia']
                ]
            );

            $inscripcionesExistentes = Inscripcion::whereHas('nivelCompetencia', function($q) use ($lista) {
                $q->where('olimpiada_id', $lista->olimpiada_id);
            })->where('postulante_id', $postulante->id)->count();

            if (($inscripcionesExistentes + count($payload['areas'])) > 2) {
                throw new \Exception('El postulante ya tiene '.$inscripcionesExistentes.' inscripciones en esta olimpiada');
            }

            // 4. Procesar áreas de inscripción
            foreach ($payload['areas'] as $area) {
                $this->crearInscripcion($postulante, $area, $lista, $payload);
            }
        });
    }

    private function mapearDatosExcel($data, $lista)
    {
        return [
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'ci' => $data['ci'],
            'fecha_nacimiento' => Carbon::createFromFormat('d-m-Y', $data['fecha_nacimiento'])->format('Y-m-d'),
            'correo_postulante' => $data['correo_postulante'],
            'curso' => $data['idCurso'],
            'departamento' => $data['idDepartamento'],
            'provincia' => $data['idProvincia'],
            'colegio' => $data['idColegio'],
            'codigo_lista' => $lista->codigo_lista,
            'areas' => $this->mapearAreas($data),
            'email_contacto' => $data['email_contacto'],
            'tipo_contacto_email' => $data['tipo_contacto_email'],
            'telefono_contacto' => $data['telefono_contacto'],
            'tipo_contacto_telefono' => $data['tipo_contacto_telefono']
        ];
    }

    private function mapearAreas($data)
    {
        $areas = [];
        if (isset($data['idArea1'])) {
            $areas[] = [
                'id_area' => $data['idArea1'],
                'id_cat' => $data['idCategoria1']
            ];
        }
        if (isset($data['idArea2']) && $data['idArea2'] !== null) {
            $areas[] = [
                'id_area' => $data['idArea2'],
                'id_cat' => $data['idCategoria2']
            ];
        }
        return $areas;
    }

    private function crearInscripcion($postulante, $area, $lista, $payload)
    {
        $nivel = NivelCompetencia::with('categoria')
        ->where('area_id', $area['id_area'])
        ->where('categoria_id', $area['id_cat'])
        ->where('olimpiada_id', $lista->olimpiada_id)
        ->first();

        if (!$nivel) {
            throw new \Exception('Combinación área-categoría inválida para la olimpiada');
        }

        // 3. Validar curso vs categoría
        $categoria = $nivel->categoria;
        if ($postulante->curso < $categoria->minimo_grado
        || $postulante->curso > $categoria->maximo_grado) {
        throw new \Exception("El curso {$postulante->curso} no es válido para la categoría {$categoria->nombre}");
    }

        Inscripcion::create([
            'postulante_id' => $postulante->id,
            'responsable_id' => $lista->responsable_id,
            'nivel_competencia_id' => $nivel->id,
            'colegio_id' => $payload['colegio'],
            'lista_id' => $lista->id,
            'email' => $payload['email_contacto'],
            'tipo_contacto_email' => $payload['tipo_contacto_email'],
            'telefono' => $payload['telefono_contacto'],
            'tipo_contacto_telefono' => $payload['tipo_contacto_telefono'],
            'estado' => 'Preinscrito'
        ]);
    }

    // Métodos reutilizables para validación
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
            'colegio' => 'required|exists:colegios,id',
            'codigo_lista' => 'required|string|exists:listas,codigo_lista',
            'areas' => 'required|array|min:1|max:2',
            'areas.*.id_area' => 'required|exists:areas,id',
            'areas.*.id_cat' => 'required|exists:categorias,id',
            'email_contacto' => 'required|email',
            'tipo_contacto_email' => 'required|in:1,2,3',
            'telefono_contacto' => 'required|string|max:8',
            'tipo_contacto_telefono' => 'required|in:1,2,3'
        ];
    }

    private function getCustomMessages()
    {
        return [
            'required' => 'El campo :attribute es obligatorio',
            'exists' => 'El valor seleccionado en :attribute no es válido',
            'max.array' => 'No puedes inscribirte en más de :max áreas',
            'between' => 'El curso debe estar entre :min y :max',
            'email' => 'El formato del correo electrónico es inválido',
            'in' => 'El valor seleccionado en :attribute no es válido',
            'max' => [
                'string' => 'El campo :attribute no debe exceder :max caracteres',
                'array' => 'No puedes seleccionar más de :max elementos en :attribute',
            ],
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
            'curso' => 'idCurso',
            'departamento' => 'idDepartamento',
            'provincia' => 'idProvincia',
            'colegio' => 'idColegio',
            'codigo_lista' => 'código de lista',
            'areas' => 'áreas de inscripción',
            'areas.0.id_area' => 'idArea1',
            'areas.0.id_cat' => 'idCategoria1',
            'areas.1.id_area' => 'idArea2',
            'areas.1.id_cat' => 'idCategoria2',
            'email_contacto' => 'email_contacto',
            'tipo_contacto_email' => 'tipo_contacto_email',
            'telefono_contacto' => 'telefono_contacto',
            'tipo_contacto_telefono' => 'tipo_contacto_telefono',

            // Mapeos adicionales para validaciones de estructura
            'idArea1' => 'idArea1',
            'idCategoria1' => 'idCategoria1',
            'idArea2' => 'idArea2',
            'idCategoria2' => 'idCategoria2',
            'idDepartamento' => 'idDepartamento',
            'idProvincia' => 'idProvincia',
            'idColegio' => 'idColegio',
            'idCurso' => 'idCurso'
        ];
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
        DB::beginTransaction();
        try {
            // 1. Validar entrada
            $data = $this->validateBulkRequest($request);

            // 2. Obtener o crear lista
            $lista = $this->obtenerOLista($data);

            // 3. Procesar postulantes
            [$exitosos, $errores] = $this->procesarPostulantesBulk($data['listaPostulantes'], $lista);

            // 4. Si hubo errores, rollback y retorno formateado
            if (!empty($errores)) {
                DB::rollBack();
                return response()->json([
                    'mensaje' => 'Se encontraron errores. No se ha creado ninguna inscripción.',
                    'errores' => $errores
                ], 400);
            }

            // 5. Commit y éxito
            DB::commit();
            return response()->json([
                'codigo_lista' => $lista->codigo_lista,
                'mensaje'      => 'Inscripción Completada',
                'exitosos'     => $exitosos,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'mensaje' => 'Error al procesar la Inscripción',
                'error'   => $e->getMessage()
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
            'listaPostulantes.*.nombres'             => 'required|string|max:255',
            'listaPostulantes.*.apellidos'           => 'required|string|max:255',
            'listaPostulantes.*.ci'                  => 'required|string|max:10',
            'listaPostulantes.*.fecha_nacimiento'    => 'required',
            'listaPostulantes.*.correo_postulante'   => 'required|email',
            'listaPostulantes.*.email_contacto'      => 'required|email',
            'listaPostulantes.*.tipo_contacto_email' => 'required|in:1,2,3',
            'listaPostulantes.*.telefono_contacto'    => 'required|string|max:8',
            'listaPostulantes.*.tipo_contacto_telefono'=> 'required|in:1,2,3',
            'listaPostulantes.*.idDepartamento'       => 'required|exists:departamentos,id',
            'listaPostulantes.*.idProvincia'          => 'required|exists:provincias,id',
            'listaPostulantes.*.idColegio'            => 'required|exists:colegios,id',
            'listaPostulantes.*.idCurso'              => 'required|integer|between:1,12',
            'listaPostulantes.*.inscripciones'        => 'required|array|min:1|max:2',
            'listaPostulantes.*.inscripciones.*.idArea'      => 'required|exists:areas,id',
            'listaPostulantes.*.inscripciones.*.idCategoria' => 'required|exists:categorias,id',
        ]);
    }

    protected function obtenerOLista(array $data): Lista
    {
        $responsable = Responsable::where('ci', $data['ci'])->firstOrFail();

        if (!empty($data['codigo_lista'])) {
            return Lista::where('codigo_lista', $data['codigo_lista'])->firstOrFail();
        }

        do {
            $codigo = Str::upper(Str::random(6));
        } while (Lista::where('codigo_lista', $codigo)->exists());

        return $responsable->listas()->create([
            'codigo_lista' => $codigo,
            'olimpiada_id' => $data['olimpiada_id'],
            'estado'       => 'Preinscrito',
        ]);
    }

    protected function procesarPostulantesBulk(array $postulantes, Lista $lista): array
    {
        $exitosos = 0;
        $errores   = [];

        foreach ($postulantes as $idx => $p) {
            $fila = $idx + 1;
            $ci   = $p['ci'];

            try {
                // Mapear payload
                $payload = [
                    'nombres'                 => $p['nombres'],
                    'apellidos'               => $p['apellidos'],
                    'ci'                      => $ci,
                    'fecha_nacimiento'        => Carbon::createFromFormat('d-m-Y', $p['fecha_nacimiento'])->format('Y-m-d'),
                    'correo_postulante'       => $p['correo_postulante'],
                    'curso'                   => $p['idCurso'],
                    'departamento'            => $p['idDepartamento'],
                    'provincia'               => $p['idProvincia'],
                    'colegio'                 => $p['idColegio'],
                    'codigo_lista'            => $lista->codigo_lista,
                    'areas'                   => array_map(
                        fn($i) => ['id_area' => $i['idArea'], 'id_cat' => $i['idCategoria']],
                        $p['inscripciones']
                    ),
                    'email_contacto'          => $p['email_contacto'],
                    'tipo_contacto_email'     => $p['tipo_contacto_email'],
                    'telefono_contacto'       => $p['telefono_contacto'],
                    'tipo_contacto_telefono'  => $p['tipo_contacto_telefono'],
                ];

                // 1) Validación interna de reglas generales
                $validator = Validator::make($payload, $this->getValidationRules(), $this->getCustomMessages());
                $validator->setAttributeNames($this->getAttributeNames());
                if ($validator->fails()) {
                    $first = $validator->errors()->first();
                    $campo = array_key_first($validator->errors()->messages());
                    throw new \Exception(
                        "error en {$campo} de la fila {$fila} del estudiante con CI {$ci}: {$first}"
                    );
                }

                // 2) Provincia vs Departamento
                $prov = Provincia::find($payload['provincia']);
                if (!$prov || $prov->departamento_id !== $payload['departamento']) {
                    throw new \Exception(
                        "error en provincia de la fila {$fila} del estudiante con CI {$ci}: Provincia no pertenece al departamento"
                    );
                }

                // 3) Crear/actualizar Postulante
                $postulante = Postulante::updateOrCreate(
                    ['ci' => $ci],
                    [
                        'nombres'          => ucwords(strtolower($payload['nombres'])),
                        'apellidos'        => ucwords(strtolower($payload['apellidos'])),
                        'fecha_nacimiento' => $payload['fecha_nacimiento'],
                        'email'            => $payload['correo_postulante'],
                        'curso'            => $payload['curso'],
                        'provincia_id'     => $payload['provincia'],
                    ]
                );

                // 4) Conteo previo de inscripciones
                $insCount = Inscripcion::where('postulante_id', $postulante->id)
                    ->whereHas('nivelCompetencia', fn($q) => $q->where('olimpiada_id', $lista->olimpiada_id))
                    ->count();
                if ($insCount + count($payload['areas']) > 2) {
                    throw new \Exception(
                        "error en inscripciones de la fila {$fila} del estudiante con CI {$ci}: Máximo 2 inscripciones permitidas"
                    );
                }

                // 5) Procesar cada área
                foreach ($payload['areas'] as $areaIdx => $area) {
                    // duplicados

                    $nivel = NivelCompetencia::where('area_id', $area['id_area'])
                        ->where('categoria_id', $area['id_cat'])
                        ->where('olimpiada_id', $lista->olimpiada_id)
                        ->first();

                    if (! $nivel) {
                        throw new \Exception(
                            "error en inscripciones de la fila {$fila} del estudiante con {$ci}: La combinación área-categoría no es válida para la olimpiada"
                        );
                    }

                    $dup = Inscripcion::where('postulante_id', $postulante->id)
                        ->whereHas('nivelCompetencia', fn($q) =>
                            $q->where('olimpiada_id', $lista->olimpiada_id)
                              ->where(fn($q2) =>
                                  $q2->where('area_id', $area['id_area'])
                                     ->orWhere('categoria_id', $area['id_cat'])
                              )
                        )
                        ->exists();
                    if ($dup) {
                        throw new \Exception(
                            "error en inscripciones de la fila {$fila} del estudiante con CI {$ci}: Área o categoría duplicada"
                        );
                    }

                    // 6) Crear inscripción dentro de sub-transacción
                    DB::transaction(function () use ($postulante, $area, $lista, $payload) {
                        $nivel = NivelCompetencia::where('area_id', $area['id_area'])
                            ->where('categoria_id', $area['id_cat'])
                            ->where('olimpiada_id', $lista->olimpiada_id)
                            ->firstOrFail();

                        Inscripcion::create([
                            'postulante_id'        => $postulante->id,
                            'responsable_id'       => $lista->responsable_id,
                            'nivel_competencia_id' => $nivel->id,
                            'colegio_id'           => $payload['colegio'],
                            'lista_id'             => $lista->id,
                            'email'                => $payload['email_contacto'],
                            'tipo_contacto_email'  => $payload['tipo_contacto_email'],
                            'telefono'             => $payload['telefono_contacto'],
                            'tipo_contacto_telefono'=> $payload['tipo_contacto_telefono'],
                            'estado'               => 'Preinscrito',
                        ]);
                    });
                }

                $exitosos++;
            } catch (\Exception $e) {
                // Ya trae formato: agregamos la cadena completa
                $errores[] = $e->getMessage();
            }
        }

        return [$exitosos, $errores];
    }
    public function getReporteDeInscripciones($olimpiada_id)
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
     * FUNCION PUENTE PARA MOSTRAR POSTULANTE O RESPONSABLE POR CI
    */
    public function showByCI($ci)
    {
        // 1. Intentamos buscar un postulante
        $postulante = Postulante::where('ci', $ci)->first();
        if ($postulante) {
            // Llamamos directamente al método existente
            return $this->showOlimpiadasByPostulanteCI($ci);
        }

        // 2. Si no es postulante, probamos con responsable
        $responsable = Responsable::where('ci', $ci)->first();
        if ($responsable) {
            return $this->showOlimpiadasByResponsableCI($ci);
        }

        // 3. Ninguno
        return response()->json(
            ['error' => 'CI no encontrado'],
            404
        );
    }

    public function showOlimpiadasByPostulanteCI($ci)
    {
        try {
            // 1. Encuentro postulante
            $postulante = Postulante::where('ci', $ci)->firstOrFail();

            // 2. Cargar inscripciones con nivelCompetencia y olimpiada
            $inscripciones = Inscripcion::with([
                    'nivelCompetencia.area',
                    'nivelCompetencia.categoria',
                    'olimpiada'
                ])
                ->where('postulante_id', $postulante->id)
                ->get();

            // 3. Agrupar por olimpiada
            $participaciones = $inscripciones
                ->groupBy(fn($ins) => $ins->olimpiada->nombre)
                ->map(function($grupo, $olimpiadaNombre) {
                    return [
                        'olimpiada' => $olimpiadaNombre,
                        'niveles_competencia' => $grupo
                            ->map(fn($ins) =>
                                "{$ins->nivelCompetencia->area->nombre} - {$ins->nivelCompetencia->categoria->nombre}"
                            )
                            ->unique()
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();

            // 4. Respuesta
            return response()->json([
                'postulante' => [
                    'nombres'    => $postulante->nombres,
                    'apellidos'  => $postulante->apellidos,
                    'ci'         => $postulante->ci,
                    'departamento' => $postulante->provincia->departamento->abreviatura,
                    'participaciones' => $participaciones,
                ]
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'CI de postulante no encontrado'], 404);
        }
    }

    public function showOlimpiadasByResponsableCI($ci)
    {
        try {
            // 1. Buscar responsable
            $responsable = Responsable::where('ci', $ci)->firstOrFail();

            // 2. Obtener inscripciones con lista asignada y cargar lista + olimpiada
            $inscripciones = Inscripcion::with(['lista', 'olimpiada'])
                ->where('responsable_id', $responsable->id)
                ->whereNotNull('lista_id')
                ->get();

            if ($inscripciones->isEmpty()) {
                return response()->json(
                    ['error' => 'Usted no tiene inscrito a ningún postulante'],
                    404
                );
            }

            // 3. Agrupar por olimpiada
            $participaciones = $inscripciones
                ->groupBy(fn($ins) => $ins->olimpiada->nombre)
                ->map(function($grupo, $olimpiadaNombre) {
                    // Dentro de esta olimpiada, agrupamos por lista
                    $listas = $grupo
                        ->groupBy('lista_id')
                        ->map(fn($listaGroup) => [
                            'codigo_lista' => $listaGroup->first()->lista->codigo_lista,
                            'cantidad'     => $listaGroup->count(),
                            'estado'       => $listaGroup->first()->estado,
                        ])
                        ->values()
                        ->all();

                    return [
                        'olimpiada' => $olimpiadaNombre,
                        'listas'    => $listas,
                    ];
                })
                ->values()
                ->all();

            // 4. Respuesta
            return response()->json([
                'responsable' => [
                    'ci'         => $responsable->ci,
                    'correo'     => $responsable->email,
                    'telefono'   => $responsable->telefono,
                    'participaciones' => $participaciones,
                ]
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'CI de responsable no encontrado'], 404);
        }
    }


}
