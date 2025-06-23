<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Cronograma;
use App\Models\Olimpiada;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\CronogramaService;


class CronogramaController extends Controller
{
    public function __construct(CronogramaService $cronogramaService)
    {
        $this->cronogramaService = $cronogramaService;
    }

    public function guardarFases(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_olimpiada' => 'required|exists:olimpiadas,id',
                'fases_agregar' => 'nullable|array',
                'fases_agregar.*' => 'required|exists:fases,id',
                'fases_borrar' => 'nullable|array',
                'fases_borrar.*' => 'required|exists:fases,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => collect($validator->errors())->flatten()->all()], 422);
            }

            $fasesGuardadas = $this->cronogramaService->actualizarFases(
                $request->input('id_olimpiada'),
                $request->input('fases_agregar', []),
                $request->input('fases_borrar', [])
            );

            return response()->json([
                'message' => 'Sincronización de fases completada.',
                'agregados' => $fasesGuardadas['agregados'],
                'borrados' => $fasesGuardadas['borrados']
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al sincronizar fases: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarFechas(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cronogramas' => 'required|array|min:1',
                'cronogramas.*.id' => 'required|exists:cronogramas,id',
                'cronogramas.*.fecha_inicio' => 'required|date',
                'cronogramas.*.fecha_fin' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => collect($validator->errors())->flatten()->all()], 422);
            }

            $cronogramasFechas = $request->input('cronogramas');

            $actualizados = $this->cronogramaService->actualizarFechas($cronogramasFechas);

            if (is_string($actualizados)) {
                return response()->json(['error' => [$actualizados]], 400);
            }

            return response()->json([
                'message' => 'Fechas actualizadas correctamente para los cronogramas.',
                'data' => $actualizados
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar fechas de cronogramas: ' . $e->getMessage()
            ], 500);
        }
    }
}
