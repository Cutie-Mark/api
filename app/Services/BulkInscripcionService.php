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
    public function storeBulk(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1) Obtener o crear Lista ⟶ se basa en ci, olimpiada_id y/o codigo_lista
            $lista = $this->obtenerOLista($data);

            // 2) Procesar cada postulante de la lista
            $exitosos = $this->procesarPostulantesBulk($data['listaPostulantes'], $lista);

            // 3) Retornar el formato requerido
            return [
                'codigo_lista' => $lista->codigo_lista,
                'mensaje' => 'Inscripción Completada',
                'exitosos' => $exitosos
            ];
        });
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
}
