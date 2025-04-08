<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Categoria;
use App\Models\Area;
use App\Models\Lista;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InscripcionController extends Controller
{
    /**
     * Crear una inscripción
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'ci' => 'required|string|max:10',
            'fecha_nacimiento' => 'required|date',
            'correo_postulante' => 'required|email',
            'curso' => 'required|integer|between:1,12',
            'departamento' => 'required|exists:departamentos,id',
            'provincia' => 'required|exists:provincias,id',
            'areas' => 'required|array|min:1',
            'areas.*.id_area' => 'required|exists:areas,id',
            'areas.*.id_cat' => 'required|exists:categorias,id',
            'email_contacto' => 'required|email',
            'tipo_contacto_email' => 'required|in:1,2,3',
            'telefono_contacto' => 'required|string|max:8',
            'tipo_contacto_telefono' => 'required|in:1,2,3',
            'colegio' => 'required|exists:colegios,id',
            'codigo_lista' => 'required|string|exists:listas,codigo_lista'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->first()], 422);
        }

        $tipoContactoMap = [
            1 => 'padre/madre',
            2 => 'profesor',
            3 => 'estudiante'
        ];

        if (!isset($tipoContactoMap[$request->tipo_contacto_email]) ||
            !isset($tipoContactoMap[$request->tipo_contacto_telefono])) {
            return response()->json(['errors' => 'Tipo de contacto inválido'], 422);
        }

        $tipoContactoEmail = $tipoContactoMap[$request->tipo_contacto_email];
        $tipoContactoTelefono = $tipoContactoMap[$request->tipo_contacto_telefono];

        return DB::transaction(function () use ($request, $tipoContactoEmail, $tipoContactoTelefono) {
            // Buscar el postulante o crearlo
            $postulante = Postulante::updateOrCreate(
                ['ci' => $request->ci],
                [
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'email' => $request->correo_postulante,
                    'curso' => $request->curso,
                    'provincia_id' => $request->provincia
                ]
            );
            
            $lista = Lista::where('codigo_lista', $request->codigo_lista)->first();
            if (!$lista) {
                return response()->json(['errors' => 'Lista no encontrada para el código proporcionado'], 404);
            }

            // Verificar límite de 2 áreas
            $existingAreasCount = Inscripcion::where('postulante_id', $postulante->id)->distinct('area_id')->count('area_id');
            $newAreasCount = collect($request->areas)->pluck('id_area')->unique()->count();

            if (($existingAreasCount + $newAreasCount) > 2) {
            return response()->json([
                'errors' => "el ci {$postulante->ci} ya se encuentra registrado en 2 áreas"
            ], 422); 
            }

            foreach ($request->areas as $area) {
                $relacionValida = DB::table('area_categoria')
                    ->where('area_id', $area['id_area'])
                    ->where('categoria_id', $area['id_cat'])
                    ->exists();

                if (!$relacionValida) {
                    return response()->json([
                        'errors' => 'La combinación área-categoría no es válida'
                    ], 400);
                }

                Inscripcion::create([
                    'postulante_id'          => $postulante->id,
                    'area_id'                => $area['id_area'],
                    'categoria_id'           => $area['id_cat'],
                    'colegio_id'             => $request->colegio,
                    'lista_id'               => $lista->id,
                    'email'                  => $request->email_contacto,
                    'tipo_contacto_email'    => $tipoContactoEmail,
                    'telefono'               => $request->telefono_contacto,
                    'tipo_contacto_telefono' => $tipoContactoTelefono,
                    'estado'                 => 'pendiente'
                ]);
            }

            return response()->json([
                'message' => 'Inscripción creada exitosamente'
            ], 201);
        });
    }


    
    /**
     * Mostrar todas las inscripciones
     */
    public function index()
    {
        $inscripciones = Inscripcion::with([
            'postulante.provincia.departamento',
            'area',
            'categoria',
            'colegio'
        ])->get()->groupBy('postulante_id');

        // Formatear respuesta
        $formattedData = $inscripciones->map(function ($inscripcionesGrupo) {
            $primeraInscripcion = $inscripcionesGrupo->first();
            $postulante = $primeraInscripcion->postulante;

            return [
                'id' => $primeraInscripcion->id,
                'nombres' => $postulante->nombres,
                'apellidos' => $postulante->apellidos,
                'ci' => $postulante->ci,
                'departamento' => $postulante->provincia->departamento->abreviatura,
                'provincia' => $postulante->provincia->nombre,
                'colegio' => $primeraInscripcion->colegio->nombre,
                'area' => $inscripcionesGrupo->pluck('area.nombre')->toArray(),
                'categoria' => $inscripcionesGrupo->pluck('categoria.nombre')->toArray(),
                'email' => $primeraInscripcion->email,
                'tipo_contacto_email' => $primeraInscripcion->tipo_contacto_email,
                'telefono' => $primeraInscripcion->telefono,
                'tipo_contacto_telefono' => $primeraInscripcion->tipo_contacto_telefono,
                'fecha_inscripcion' => $primeraInscripcion->fecha_inscripcion,
                'estado' => $primeraInscripcion->estado
            ];
        })->values();

        return response()->json(['data' => $formattedData], 200);
    }


    /**
     * Mostra una inscripcion por id
     */
    public function show($id)
    {
        $inscripcion = Inscripcion::with([
            'postulante.provincia.departamento',
            'area',
            'categoria',
            'colegio'
        ])->find($id);

        if (!$inscripcion) {
            return response()->json(['errors' => 'Inscripción no encontrada'], 404);
        }

        $inscripcionesGrupo = Inscripcion::where('postulante_id', $inscripcion->postulante_id)
            ->with(['area', 'categoria'])
            ->get();

        $formattedInscripcion = [
            'id' => $inscripcion->id,
            'nombres' => $inscripcion->postulante->nombres,
            'apellidos' => $inscripcion->postulante->apellidos,
            'ci' => $inscripcion->postulante->ci,
            'departamento' => $inscripcion->postulante->provincia->departamento->abreviatura,
            'provincia' => $inscripcion->postulante->provincia->nombre,
            'colegio' => $inscripcion->colegio->nombre,
            'area' => $inscripcionesGrupo->pluck('area.nombre')->toArray(),
            'categoria' => $inscripcionesGrupo->pluck('categoria.nombre')->toArray(),
            'email' => $inscripcion->email,
            'tipo_contacto_email' => $inscripcion->tipo_contacto_email,
            'telefono' => $inscripcion->telefono,
            'tipo_contacto_telefono' => $inscripcion->tipo_contacto_telefono,
            'fecha_inscripcion' => $inscripcion->fecha_inscripcion,
            'estado' => $inscripcion->estado
        ];

        return response()->json(['data' => $formattedInscripcion], 200);
    }
    


    /**
     * Mostrar inscripciones por estado
     */
    public function getByEstado($estado)
    {
        if (!in_array($estado, ['pendiente', 'pagado'])) {
            return response()->json(['errors' => 'Estado no válido'], 400);
        }

        $inscripciones = Inscripcion::with([
                'postulante:id,nombres,apellidos,ci',
                'area:id,nombre',
                'categoria:id,nombre'
            ])
            ->where('estado', $estado)
            ->get()
            ->groupBy('postulante_id');

        $formattedData = $this->formatGroupedInscripciones($inscripciones);

        return response()->json([
            'count' => count($formattedData),
            'data' => $formattedData
        ], 200);
    }


    /**
     * Mostrar cantidad de inscritos en un área
     */
    public function countByArea($areaId)
    {
        $count = Inscripcion::where('area_id', $areaId)
                ->select(DB::raw('COUNT(DISTINCT postulante_id) as total'))
                ->value('total');

        $areaNombre = Area::find($areaId)->nombre ?? 'Área no encontrada';

        return response()->json([
            'area_id' => $areaId,
            'area_nombre' => $areaNombre,
            'total_inscritos' => $count
        ], 200);
    }


    /**
     * Mostrar cantidad de inscritos en una categoría
     */ 
    public function countByCategoria($categoriaId)
    {
        $count = Inscripcion::where('categoria_id', $categoriaId)
                ->select(DB::raw('COUNT(DISTINCT postulante_id) as total'))
                ->value('total');

        $categoriaNombre = Categoria::find($categoriaId)->nombre ?? 'Categoría no encontrada';

        return response()->json([
            'categoria_id' => $categoriaId,
            'categoria_nombre' => $categoriaNombre,
            'total_inscritos' => $count
        ], 200);
    }

    // Función auxiliar para formatear inscripciones agrupadas
    private function formatGroupedInscripciones($inscripcionesGrupo)
    {
        return $inscripcionesGrupo->map(function ($inscripciones) {
            $primera = $inscripciones->first();
            return [
                'postulante_id' => $primera->postulante_id,
                'nombres' => $primera->postulante->nombres,
                'apellidos' => $primera->postulante->apellidos,
                'ci' => $primera->postulante->ci,
                'areas' => $inscripciones->pluck('area.nombre')->unique()->values(),
                'categorias' => $inscripciones->pluck('categoria.nombre')->unique()->values(),
                'estado' => $primera->estado
            ];
        })->values();
    }
    
    
    /**
     * Mostrar lista de inscritos en un area
     */ 
    public function getInscripcionesByArea($areaId)
    {
        $validator = Validator::make(['area_id' => $areaId], [
            'area_id' => 'required|exists:areas,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => 'Área no válida'], 400);
        }

        $inscripciones = Inscripcion::with([
                'postulante.provincia.departamento',
                'categoria',
                'colegio'
            ])
            ->where('area_id', $areaId)
            ->get()
            ->map(function ($inscripcion) {
                return [
                    'id' => $inscripcion->id,
                    'postulante' => [
                        'nombres' => $inscripcion->postulante->nombres,
                        'apellidos' => $inscripcion->postulante->apellidos,
                        'ci' => $inscripcion->postulante->ci,
                        'departamento' => $inscripcion->postulante->provincia->departamento->abreviatura,
                        'provincia' => $inscripcion->postulante->provincia->nombre,
                        'colegio' => $inscripcion->colegio->nombre,
                        'categoria' => $inscripcion->categoria->nombre,
                        'estado' => $inscripcion->estado
                    ]
                ];
            });

        return response()->json([
            'area' => Area::find($areaId)->nombre,
            'total' => $inscripciones->count(),
            'data' => $inscripciones
        ], 200);
    }


    /**
     * Mostrar lista de inscritos en una categoria
     */ 
    public function getInscripcionesByCategoria($categoriaId)
    {
        $validator = Validator::make(['categoria_id' => $categoriaId], [
            'categoria_id' => 'required|exists:categorias,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => 'Categoría no válida'], 400);
        }

        $inscripciones = Inscripcion::with([
                'postulante.provincia.departamento',
                'categoria',
                'colegio'
            ])
            ->where('categoria_id', $categoriaId)
            ->get()
            ->map(function ($inscripcion) {
                return [
                    'id' => $inscripcion->id,
                    'postulante' => [
                        'nombres' => $inscripcion->postulante->nombres,
                        'apellidos' => $inscripcion->postulante->apellidos,
                        'ci' => $inscripcion->postulante->ci,
                        'departamento' => $inscripcion->postulante->provincia->departamento->abreviatura,
                        'provincia' => $inscripcion->postulante->provincia->nombre,
                        'colegio' => $inscripcion->colegio->nombre,
                        'area' => $inscripcion->area->nombre,
                        'estado' => $inscripcion->estado
                    ]
                ];
            });

        return response()->json([
            'categoria' => Categoria::find($categoriaId)->nombre,
            'total' => $inscripciones->count(),
            'data' => $inscripciones
        ], 200);
    }

    /**
     * Actualizar estado de inscripcion
     */ 
    public function updateEstadoInscripcion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:pendiente,pagado'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->first()], 422);
        }

        try {
            $inscripcion = Inscripcion::findOrFail($id);
        } catch(ModelNotFoundException $e) {
            return response()->json(['errors' => 'Inscripción no encontrada'], 404);
        }

        $inscripcion->estado = $request->estado;
        $inscripcion->save();

        return response()->json([
            'data' => [
                'id_inscripcion' => $inscripcion->id,
                'estado_actualizado' => $inscripcion->estado
            ]
        ], 200);
    }

    /**
     * Mostrar inscripcion por CI
     */ 
    public function getInscripcionByCI($ci)
    {
        $postulante = Postulante::where('ci', $ci)->first();
        if (!$postulante) {
            return response()->json(['errors' => 'Postulante no encontrado'], 404);
        }

        $inscripciones = Inscripcion::with(['area', 'categoria', 'colegio'])
            ->where('postulante_id', $postulante->id)
            ->get();

        if ($inscripciones->isEmpty()) {
            return response()->json(['errors' => 'El postulante no tiene inscripciones'], 404);
        }

        $firstInscripcion = $inscripciones->first();

        $responsable = [
            'nombre_completo' => $postulante->nombres . ' ' . $postulante->apellidos,
            'ci'              => $postulante->ci,
            'telefono'        => $firstInscripcion->telefono
        ];

        $formattedInscripciones = $inscripciones->map(function ($inscripcion) {
            return [
                'id' => $inscripcion->id,
                'area' => $inscripcion->area->nombre,
                'categoria' => $inscripcion->categoria->nombre,
                'colegio' => $inscripcion->colegio->nombre,
                'estado' => $inscripcion->estado,
                'fecha_inscripcion' => $inscripcion->fecha_inscripcion
            ];
        })->values();

        return response()->json([
            'postulante' => [
                'nombres'           => $postulante->nombres,
                'apellidos'         => $postulante->apellidos,
                'ci'                => $postulante->ci,
                'fecha_nacimiento'  => $postulante->fecha_nacimiento,
                'email'             => $postulante->email,
                'curso'             => $postulante->curso
            ],
            'responsable' => $responsable,
            'inscripciones' => $formattedInscripciones
        ], 200);
    }

}