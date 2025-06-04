<?php

namespace App\Http\Controllers;

use App\Models\Postulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PostulanteController extends Controller
{
    /**
     * Crear un nuevo postulante
     */
    public function store(Request $request)
    {
        // 1) Validación básica (puede usar unique para email, pero NOT para ci)
        $validator = Validator::make($request->all(), [
            'nombres'           => 'required|string|max:255',
            'apellidos'         => 'required|string|max:255',
            'ci'                => 'required|string|max:20',
            'fecha_nacimiento'  => 'required|date_format:Y-m-d',
            'provincia_id'      => 'required|integer|exists:provincias,id',
            'email'             => 'required|email|unique:postulantes,email',
            'curso'             => 'required|string|max:50',
        ], [
            'required'            => 'El campo :attribute es obligatorio',
            'date_format'         => 'El campo :attribute debe tener formato YYYY-MM-DD',
            'provincia_id.exists' => 'La provincia indicada no existe',
            'email.unique'        => 'Ya existe un postulante con ese correo',
        ])->setAttributeNames([
            'nombres'          => 'Nombres',
            'apellidos'        => 'Apellidos',
            'ci'               => 'CI',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'provincia_id'     => 'Provincia',
            'email'            => 'Email',
            'curso'            => 'Curso',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $data = $validator->validated();

        // 2) Verificar unicidad de CI desencriptado
        $ciPlano = $data['ci'];
        $existeCi = Postulante::all()->contains(function ($p) use ($ciPlano) {
            try {
                return Crypt::decryptString($p->getRawOriginal('ci')) === $ciPlano;
            } catch (\Exception $e) {
                return false;
            }
        });
        if ($existeCi) {
            return response()->json([
                'error' => 'Ya existe un postulante con esa Cédula de Identidad'
            ], 422);
        }

        // 3) Crear postulante (el modelo encripta nombres, apellidos y ci)
        $postulante = Postulante::create([
            'nombres'          => $data['nombres'],
            'apellidos'        => $data['apellidos'],
            'ci'               => $ciPlano,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'provincia_id'     => $data['provincia_id'],
            'email'            => $data['email'],  // este campo queda sin cifrar
            'curso'            => $data['curso'],
        ]);

        return response()->json([
            'mensaje' => 'Postulante creado exitosamente',
            'data'    => [
                'id'               => $postulante->id,
                'nombres'          => $postulante->nombres,           // desencriptado
                'apellidos'        => $postulante->apellidos,         // desencriptado
                'ci'               => $postulante->ci,                // desencriptado
                'fecha_nacimiento' => $postulante->fecha_nacimiento->toDateString(),
                'provincia_id'     => $postulante->provincia_id,
                'email'            => $postulante->email,
                'curso'            => $postulante->curso,
            ],
        ], 201);
    }

    /**
     * Listar todos los postulantes (desencriptando automáticamente nombres, apellidos y ci)
     */
    public function index()
    {
        $postulantes = Postulante::with('contactos')
            ->get()
            ->map(function ($p) {
                return [
                    'id'               => $p->id,
                    'nombres'          => $p->nombres,    // accessor desencripta
                    'apellidos'        => $p->apellidos,  // accessor desencripta
                    'ci'               => $p->ci,         // accessor desencripta
                    'fecha_nacimiento' => $p->fecha_nacimiento->toDateString(),
                    'provincia_id'     => $p->provincia_id,
                    'email'            => $p->email,
                    'curso'            => $p->curso,
                    'contactos'        => $p->contactos->map(function ($c) {
                        return [
                            'telefono' => $c->telefono,
                            'email'    => $c->email,
                        ];
                    }),
                ];
            });

        return response()->json([
            'data' => $postulantes
        ], 200);
    }

    /**
     * Mostrar un postulante por ID
     */
    public function show($id)
    {
        try {
            $p = Postulante::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Postulante no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'               => $p->id,
                'nombres'          => $p->nombres,
                'apellidos'        => $p->apellidos,
                'ci'               => $p->ci,
                'fecha_nacimiento' => $p->fecha_nacimiento->toDateString(),
                'provincia_id'     => $p->provincia_id,
                'email'            => $p->email,
                'curso'            => $p->curso,
                'contactos'        => $p->contactos->map(function ($c) {
                    return [
                        'telefono' => $c->telefono,
                        'email'    => $c->email,
                    ];
                }),
            ]
        ], 200);
    }

    /**
     * Actualizar un postulante por ID
     */
    public function update(Request $request, $id)
    {
        try {
            $p = Postulante::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Postulante no encontrado'
            ], 404);
        }

        // 1) Validar campos opcionales (email sí puede usar unique aquí)
        $validator = Validator::make($request->all(), [
            'nombres'           => 'sometimes|required|string|max:255',
            'apellidos'         => 'sometimes|required|string|max:255',
            'ci'                => 'sometimes|required|string|max:20',
            'fecha_nacimiento'  => 'sometimes|required|date_format:Y-m-d',
            'provincia_id'      => 'sometimes|required|integer|exists:provincias,id',
            'email'             => 'sometimes|required|email|unique:postulantes,email,' . $id,
            'curso'             => 'sometimes|required|string|max:50',
        ], [
            'required'            => 'El campo :attribute es obligatorio',
            'date_format'         => 'El campo :attribute debe tener formato YYYY-MM-DD',
            'provincia_id.exists' => 'La provincia indicada no existe',
            'email.unique'        => 'Ya existe un postulante con ese correo',
        ])->setAttributeNames([
            'nombres'          => 'Nombres',
            'apellidos'        => 'Apellidos',
            'ci'               => 'CI',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'provincia_id'     => 'Provincia',
            'email'            => 'Email',
            'curso'            => 'Curso',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $data = $validator->validated();

        // 2) Si cambian “ci”, verificar unicidad desencriptada
        if (isset($data['ci'])) {
            $ciPlanoNuevo = $data['ci'];
            $existeOtroCi = Postulante::all()->contains(function ($post) use ($ciPlanoNuevo, $id) {
                try {
                    return ($post->id !== (int)$id)
                        && (Crypt::decryptString($post->getRawOriginal('ci')) === $ciPlanoNuevo);
                } catch (\Exception $e) {
                    return false;
                }
            });
            if ($existeOtroCi) {
                return response()->json([
                    'error' => 'Ya existe otro postulante con esa Cédula'
                ], 422);
            }
        }

        // 3) Actualizar el postulante (el modelo cifrará si vienen nombres/apellidos/ci)
        $p->update($data);

        return response()->json([
            'mensaje' => 'Postulante actualizado correctamente',
            'data'    => [
                'id'               => $p->id,
                'nombres'          => $p->nombres,
                'apellidos'        => $p->apellidos,
                'ci'               => $p->ci,
                'fecha_nacimiento' => $p->fecha_nacimiento->toDateString(),
                'provincia_id'     => $p->provincia_id,
                'email'            => $p->email,
                'curso'            => $p->curso,
            ],
        ], 200);
    }

    /**
     * Buscar un postulante por CI (texto plano).
     */
    public function showByCi($ci)
    {
        $postulante = Postulante::all()->first(function ($post) use ($ci) {
            try {
                return Crypt::decryptString($post->getRawOriginal('ci')) === $ci;
            } catch (\Exception $e) {
                return false;
            }
        });

        if (!$postulante) {
            return response()->json([
                'error' => 'Postulante no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'               => $postulante->id,
                'nombres'          => $postulante->nombres,
                'apellidos'        => $postulante->apellidos,
                'ci'               => $postulante->ci,
                'fecha_nacimiento' => $postulante->fecha_nacimiento->toDateString(),
                'provincia_id'     => $postulante->provincia_id,
                'email'            => $postulante->email,
                'curso'            => $postulante->curso,
            ]
        ], 200);
    }
}
