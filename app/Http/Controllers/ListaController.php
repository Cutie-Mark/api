<?php

namespace App\Http\Controllers;

use App\Models\Lista;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ListaController extends Controller
{
    /**
     * Crear lista que se asocia al responsable y a una olimpiada
     */
    public function crear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ci'            => 'required|string',
            'olimpiada_id'  => 'required|exists:olimpiadas,id'
        ], [
            'required'      => 'El campo :attribute es obligatorio',
            'exists'        => 'La olimpiada seleccionada no existe'
        ])->setAttributeNames([
            'ci'            => 'CI',
            'olimpiada_id'  => 'ID de Olimpiada'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        // Buscar responsable por CI desencriptado
        $responsable = null;
        $allResponsables = Responsable::all();
        foreach ($allResponsables as $resp) {
            if ($resp->ci === $request->ci) {
                $responsable = $resp;
                break;
            }
        }

        if (!$responsable) {
            return response()->json([
                'error' => 'Responsable no encontrado con el CI proporcionado'
            ], 404);
        }

        $lista = $responsable->listas()->create([
            'olimpiada_id'  => $request->olimpiada_id,
        ]);

        return response()->json([
            'codigo_lista' => $lista->codigo_lista
        ], 201);
    }

    /**
     * Mostrar todas las listas
     */
    public function listar()
    {
        $listas = Lista::withCount([
            'inscripciones as postulantes_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT postulante_id)'));
            }
        ])->get();

        $filtered = $listas->map(fn($lista) => [
            'codigo lista'      => $lista->codigo_lista,
            'olimpiada id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'cantidad postulantes' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $filtered], 200);
    }

    /**
     * Mostrar lista por id
     */
    public function mostrar($id)
    {
        $lista = Lista::withCount([
            'inscripciones as postulantes_count' => function ($query) {
                $query->select(DB::raw('COUNT(DISTINCT postulante_id)'));
            }
        ])->find($id);

        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        return response()->json([
            'data' => [
                'codigo_lista'      => $lista->codigo_lista,
                'olimpiada_id'      => $lista->olimpiada_id,
                'estado'            => $lista->estado,
                'postulantes_count' => $lista->postulantes_count,
                'created_at'        => $lista->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Mostrar listas de un responsable por CI
     */
    public function mostrarResponsablePorCI($ci)
    {
        // Buscar responsable por CI desencriptado
        $responsable = null;
        $allResponsables = Responsable::all();
        foreach ($allResponsables as $resp) {
            if ($resp->ci === $ci) {
                $responsable = $resp;
                break;
            }
        }
        
        if (!$responsable) {
            return response()->json(['error' => 'Responsable no encontrado'], 404);
        }

        $listas = $responsable->listas()
            ->withCount(['inscripciones as postulantes_count'])
            ->get();

        $formatted = $listas->map(fn($lista) => [
            'codigo_lista'      => $lista->codigo_lista,
           // 'nombre_lista'      => $lista->nombre_lista,
            'olimpiada_id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'postulantes_count' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $formatted], 200);
    }

    /**
     * Mostrar listas por estado
     */
    public function mostrarListasPorEstado($estado)
    {
        if (!in_array($estado, ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])) {
            return response()->json(['error' => 'Estado no válido. Use: pendiente o pagado'], 400);
        }

        $listas = Lista::where('estado', $estado)
            ->withCount(['inscripciones as postulantes_count'])
            ->get();

        $formatted = $listas->map(fn($lista) => [
            'codigo_lista'      => $lista->codigo_lista,
            'olimpiada_id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'postulantes_count' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $formatted], 200);
    }

    /**
     * Mostrar listas por estado y responsable
     */
    public function mostraListasPorEstadoYResponsable($ci, $estado)
    {
        if (!in_array($estado, ['Preinscrito', 'Pago Pendiente', 'Inscripcion Completa'])) {
            return response()->json(['error' => 'Estado no válido. Use: pendiente o pagado'], 400);
        }

        // Buscar responsable por CI desencriptado
        $responsable = null;
        $allResponsables = Responsable::all();
        foreach ($allResponsables as $resp) {
            if ($resp->ci === $ci) {
                $responsable = $resp;
                break;
            }
        }
        
        if (!$responsable) {
            return response()->json(['error' => 'Responsable no encontrado'], 404);
        }

        $listas = $responsable->listas()
            ->where('estado', $estado)
            ->withCount(['inscripciones as postulantes_count'])
            ->get();

        $formatted = $listas->map(fn($lista) => [
            'codigo_lista'      => $lista->codigo_lista,
            'olimpiada_id'      => $lista->olimpiada_id,
            'estado'            => $lista->estado,
            'postulantes_count' => $lista->postulantes_count,
            'created_at'        => $lista->created_at->toDateTimeString(),
        ]);

        return response()->json(['data' => $formatted], 200);
    }

    /**
     * Mostrar lista por código
     */
    public function mostrarPorCodigo($codigo)
    {
        $lista = Lista::with([
                'inscripciones.postulante',
                'inscripciones.nivelCompetencia.area',
                'inscripciones.nivelCompetencia.categoria',
            ])
            ->where('codigo_lista', $codigo)
            ->first();

        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        $data = $lista->inscripciones->map(function ($inscripcion) {
            $post     = $inscripcion->postulante;
            $nc       = $inscripcion->nivelCompetencia;
            $cursoNum = (int) $post->curso;

            if ($cursoNum >= 1 && $cursoNum <= 6) {
                $grado = $cursoNum;
                $nivel = 'Primaria';
            } elseif ($cursoNum >= 7 && $cursoNum <= 12) {
                $grado = $cursoNum - 6;
                $nivel = 'Secundaria';
            } else {
                $grado = $cursoNum;
                $nivel = '';
            }

            $ordinals = [
                1 => '1ro', 2 => '2do', 3 => '3ro',
                4 => '4to', 5 => '5to', 6 => '6to',
            ];
            $ordinal   = $ordinals[$grado] ?? $grado;
            $cursoTexto = trim("{$ordinal} {$nivel}");

            return [
                'id'               => (string) $post->id,
                'nombres'          => $post->nombres,
                'apellidos'        => $post->apellidos,
                'fecha_nacimiento' => optional($post->fecha_nacimiento)->format('Y-m-d'),
                'provincia_id'     => (string) $post->provincia_id,
                'email'            => $post->email,
                'ci'               => $post->ci,
                'curso'            => $cursoTexto,
                'area'             => optional($nc->area)->nombre,
                'categoria'        => optional($nc->categoria)->nombre,
            ];
        });

        return response()->json([
            'estado' => $lista->estado,
            'data'   => $data,
        ], 200);
    }

    /**
     * Actualizar el estado de una lista
     */
    public function ActualizarEstado(Request $request, $codigo)
    {
        $request->validate([
            'estado' => 'required|string|in:Preinscrito,Pago Pendiente,Inscripcion Completa',
        ]);

        $lista = Lista::where('codigo_lista', $codigo)->first();
        if (!$lista) {
            return response()->json(['error' => 'Lista no encontrada'], 404);
        }

        $lista->estado = $request->estado;
        $lista->save();

        return response()->json([
            'data' => [
                'codigo_lista'       => $lista->codigo_lista,
                'estado_actualizado' => $lista->estado
            ]
        ], 200);
    }

    /**
     * Mostrar listas por olimpiada
     */
    public function mostrarPorOlimpiada($olimpiadaId)
    {
        $listas = Lista::where('olimpiada_id', $olimpiadaId)
            ->withCount(['inscripciones as postulantes_count'])
            ->get()
            ->map(fn($lista) => [
                'codigo_lista'      => $lista->codigo_lista,
                //'nombre_lista'      => $lista->nombre_lista,
                'olimpiada_id'      => $lista->olimpiada_id,
                'estado'            => $lista->estado,
                'postulantes_count' => $lista->postulantes_count,
                'created_at'        => $lista->created_at->toDateTimeString(),
            ]);

        return response()->json(['data' => $listas], 200);
    }


    /**
     * Eliminar una lista solo si NO tiene postulantes vinculados
     */
    public function eliminarListaVacia($codigo)
    {
        try {
            DB::beginTransaction();

            // Buscar lista con conteo de postulantes
            $lista = Lista::where('codigo_lista', $codigo)
                ->withCount(['inscripciones as postulantes_count' => function ($query) {
                    $query->select(DB::raw('COUNT(DISTINCT postulante_id)'));
                }])
                ->first();

            if (!$lista) {
                return response()->json(['error' => 'Lista no encontrada'], 404);
            }

            // Validar si tiene postulantes
            if ($lista->postulantes_count > 0) {
                return response()->json([
                    'error' => 'No se puede eliminar la lista porque contiene postulantes.'
                ], 400);
            }

            // Eliminar inscripciones (si existen)
            $lista->inscripciones()->delete();

            // Eliminar la lista
            $lista->delete();

            DB::commit();

            return response()->json([
                'mensaje' => 'Lista eliminada correctamente.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error eliminando lista: " . $e->getMessage(), [
                'exception' => $e,
                'codigo_lista' => $codigo
            ]);
            return response()->json([
                'error' => 'Error interno al eliminar la lista.'
            ], 500);
        }
    }
}