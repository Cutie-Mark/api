<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cronograma;
use App\Models\Olimpiada;
use App\Models\Fase;
use Carbon\Carbon;

class CronogramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener todas las olimpiadas
        $olimpiadas = Olimpiada::all();
        
        // Obtener las fases relevantes
        $fases = Fase::whereIn('nombre_fase', [
            'Preparación', 
            'Lanzamiento', 
            'Primera inscripción', 
            'Segunda inscripción', 
            'Tercera inscripción', 
            'Cuarta inscripción',
            'Primera clasificación',
            'Segunda clasificación',
            'Tercera clasificación',
            'Final',
            'Premiación'
        ])->get();
        
        foreach ($olimpiadas as $olimpiada) {
            // Creamos un cronograma específico para cada olimpiada
            $fechaInicio = Carbon::parse($olimpiada->fecha_inicio);
            $fechaFin = Carbon::parse($olimpiada->fecha_fin);
            
            // Distribución de fechas para fases (ajustamos según la olimpiada)
            // Este intervalo representa el periodo total en días
            $totalDias = $fechaInicio->diffInDays($fechaFin);
            $intervalo = floor($totalDias / 10); // Dividimos en 10 intervalos para las fases
            
            foreach ($fases as $fase) {
                switch ($fase->nombre_fase) {
                    case 'Preparación':
                        // Antes del inicio oficial
                        $inicio = $fechaInicio->copy()->subDays(14);
                        $fin = $fechaInicio->copy()->subDays(1);
                        break;
                    case 'Lanzamiento':
                        // Primeros días
                        $inicio = $fechaInicio->copy();
                        $fin = $fechaInicio->copy()->addDays(3);
                        break;
                    case 'Primera inscripción':
                        // Después del lanzamiento
                        $inicio = $fechaInicio->copy()->addDays(4);
                        $fin = $fechaInicio->copy()->addDays($intervalo);
                        break;
                    case 'Segunda inscripción':
                        // Después de primera inscripción
                        $inicio = $fechaInicio->copy()->addDays($intervalo + 1);
                        $fin = $fechaInicio->copy()->addDays($intervalo * 2);
                        break;
                    case 'Tercera inscripción':
                        // Después de segunda inscripción
                        $inicio = $fechaInicio->copy()->addDays($intervalo * 2 + 1);
                        $fin = $fechaInicio->copy()->addDays($intervalo * 3);
                        break;
                    case 'Cuarta inscripción':
                        // Después de tercera inscripción
                        $inicio = $fechaInicio->copy()->addDays($intervalo * 3 + 1);
                        $fin = $fechaInicio->copy()->addDays($intervalo * 4);
                        break;
                    case 'Primera clasificación':
                        $inicio = $fechaInicio->copy()->addDays($intervalo * 4 + 1);
                        $fin = $fechaInicio->copy()->addDays($intervalo * 5);
                        break;
                    case 'Segunda clasificación':
                        $inicio = $fechaInicio->copy()->addDays($intervalo * 5 + 1);
                        $fin = $fechaInicio->copy()->addDays($intervalo * 6);
                        break;
                    case 'Tercera clasificación':
                        $inicio = $fechaInicio->copy()->addDays($intervalo * 6 + 1);
                        $fin = $fechaInicio->copy()->addDays($intervalo * 7);
                        break;
                    case 'Final':
                        $inicio = $fechaInicio->copy()->addDays($intervalo * 7 + 1);
                        $fin = $fechaInicio->copy()->addDays($intervalo * 8);
                        break;
                    case 'Premiación':
                        // Últimos días antes del fin
                        $inicio = $fechaInicio->copy()->addDays($intervalo * 8 + 1);
                        $fin = $fechaFin->copy();
                        break;
                    default:
                        continue 2; // Saltar esta iteración si no coincide con ninguna fase
                }
                
                // Crear el cronograma para esta fase y olimpiada
                Cronograma::create([
                    'id_fase' => $fase->id,
                    'fecha_inicio' => $inicio->format('Y-m-d'),
                    'fecha_fin' => $fin->format('Y-m-d'),
                    'olimpiada_id' => $olimpiada->id,
                ]);
            }
        }
    }
}
