<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Postulante;
use App\Models\Lista;
use App\Models\NivelCompetencia;
use App\Models\Area;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
            'max.array' => 'No puedes inscribirte en más de :max áreas',
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

            // Obtener lista
            $lista = Lista::where('codigo_lista', $request->codigo_lista)->firstOrFail();

            // Crear inscripciones por cada área
            foreach ($request->areas as $areaInput) {
                $nivel = NivelCompetencia::where('area_id', $areaInput['id_area'])
                    ->where('categoria_id', $areaInput['id_cat'])
                    ->where('olimpiada_id', $lista->olimpiada_id)
                    ->first();

                if (!$nivel) {
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
                    'estado'               => 'pendiente'
                ]);
            }

            return response()->json(['message' => 'Inscripción(es) creada(s) exitosamente'], 201);
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
        if (!in_array($estado, ['pendiente', 'pagado'])) {
            return response()->json(['error' => 'Estado no válido'], 400);
        }

        $inscripciones = Inscripcion::with([
                'postulante:id,nombres,apellidos,ci',
                'nivelCompetencia.area:id,nombre',
                'nivelCompetencia.categoria:id,nombre'
            ])
            ->where('estado', $estado)
            ->get()
            ->groupBy('postulante_id');

        $formatted = $this->formatGroupedInscripciones($inscripciones);

        return response()->json([
            'count' => count($formatted),
            'data'  => $formatted
        ], 200);
    }

    /**
     * Actualizar estado de inscripcion
     */
    public function updateEstadoInscripcion(Request $request, $id)
    {
        // Validar nuevo estado
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:pendiente,pagado'
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
     * Mostrar inscripciones de un postulante por CI
     */
    public function getInscripcionByCI($ci)
    {
        $postulante = Postulante::where('ci', $ci)->first();
        if (!$postulante) {
            return response()->json(['error' => 'Postulante no encontrado'], 404);
        }

        $inscripciones = Inscripcion::with(['nivelCompetencia.area', 'nivelCompetencia.categoria', 'colegio'])
            ->where('postulante_id', $postulante->id)
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
                    'categoria'   => $ins->nivelCompetencia->categoria->nombre,
                    'estado'      => $ins->estado
                ]
            ]);
        if ($inscripciones->isEmpty()) {    
            return response()->json(['error' => 'No se encontraron inscripciones para este postulante'], 404);
        }
        return response()->json([
            'postulante' => [
                'nombres'    => $postulante->nombres,
                'apellidos'  => $postulante->apellidos,
                'ci'         => $postulante->ci,
                'departamento'=> $postulante->provincia->departamento->abreviatura,
                'provincia'   => $postulante->provincia->nombre
            ],
            'inscripciones' => $inscripciones
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
}