<?php

namespace App\Http\Controllers;

use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

class ResponsableController extends Controller
{
    /**
     * Registra un nuevo responsable
     */
    public function store(Request $request)
    {
        // 1. Validación básica (sin unique en 'ci')
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:255',
            'ci'              => 'required|string|max:15',
            'email'           => 'required|email|unique:responsables,email',
            'telefono'        => 'required|string|max:8',
        ], [
            'required'         => 'El campo :attribute es obligatorio',
            'email.unique'     => 'Ya existe una cuenta registrada con el correo',
        ])->setAttributeNames([
            'nombre_completo' => 'Nombre Completo',
            'ci'              => 'CI',
            'email'           => 'Email',
            'telefono'        => 'Telefono',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $data = $validator->validated();
        $ciPlano = $data['ci'];

        // 2. Verificar unicidad de CI desencriptado
        $existeCi = Responsable::all()->contains(function ($r) use ($ciPlano) {
            try {
                return Crypt::decryptString($r->getRawOriginal('ci')) === $ciPlano;
            } catch (\Exception $e) {
                return false;
            }
        });

        if ($existeCi) {
            return response()->json([
                'error' => 'Ya existe una cuenta registrada con el CI'
            ], 422);
        }

        // 3. Crear nuevo responsable
        //    El modelo Responsable se encargará de encriptar 'nombre_completo' y 'ci'.
        $responsable = Responsable::create([
            'nombre_completo' => $data['nombre_completo'],
            'ci'              => $ciPlano,
            'email'           => $data['email'],
            'telefono'        => $data['telefono'],
        ]);

        return response()->json([
            'mensaje' => 'Registro de responsable exitoso',
            'data'    => [
                'id'              => $responsable->id,
                'nombre_completo' => $responsable->nombre_completo, // ya viene desencriptado por el accessor
                'ci'              => $responsable->ci,
                'email'           => $responsable->email,
                'telefono'        => $responsable->telefono,
            ]
        ], 201);
    }

    /**
     * Obtener todos los responsables (concierto de desencriptado automático)
     */
    public function index()
    {
        $responsables = Responsable::all()->map(function ($r) {
            return [
                'id'              => $r->id,
                'nombre_completo' => $r->nombre_completo, // accessor desencripta
                'ci'              => $r->ci,              // accessor desencripta
                'email'           => $r->email,
                'telefono'        => $r->telefono,
            ];
        });

        return response()->json([
            'data' => $responsables
        ], 200);
    }

    /**
     * Obtener responsable por id
     */
    public function show($id)
    {
        $r = Responsable::find($id);
        if (!$r) {
            return response()->json([
                'error' => 'Responsable no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'              => $r->id,
                'nombre_completo' => $r->nombre_completo,
                'ci'              => $r->ci,
                'email'           => $r->email,
                'telefono'        => $r->telefono,
            ]
        ], 200);
    }

    /**
     * Actualizar responsable por id
     */
    public function update(Request $request, $id)
    {
        $r = Responsable::find($id);
        if (!$r) {
            return response()->json([
                'error' => 'Responsable no encontrado'
            ], 404);
        }

        // 1. Validar campos opcionales (sin unique directo en ci):
        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'sometimes|required|string|max:255',
            'ci'              => 'sometimes|required|string|max:15',
            'email'           => 'sometimes|required|email|unique:responsables,email,' . $id,
            'telefono'        => 'sometimes|required|string|max:8',
        ], [
            'required'     => 'El campo :attribute es obligatorio',
            'email.unique' => 'Ya existe una cuenta registrada con el correo',
        ])->setAttributeNames([
            'nombre_completo' => 'Nombre Completo',
            'ci'              => 'CI',
            'email'           => 'Email',
            'telefono'        => 'Telefono',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $data = $validator->validated();

        // 2. Si vienen "nombre_completo" y/o "ci", verificar unicidad de CI desencriptado
        if (isset($data['ci'])) {
            $ciPlanoNuevo = $data['ci'];
            $existeOtroCi = Responsable::all()->contains(function ($resp) use ($ciPlanoNuevo, $id) {
                try {
                    // Desencriptar el CI de cada responsable y comparar, 
                    // descartando el propio registro ($id)
                    return ($resp->id !== (int)$id)
                        && (Crypt::decryptString($resp->getRawOriginal('ci')) === $ciPlanoNuevo);
                } catch (\Exception $e) {
                    return false;
                }
            });
            if ($existeOtroCi) {
                return response()->json([
                    'error' => 'Ya existe otra cuenta con ese CI'
                ], 422);
            }
        }

        // 3. Actualizar:
        //    Si se envía 'nombre_completo' o 'ci', el modelo se encargará de encriptar.
        $r->update($data);

        return response()->json([
            'mensaje' => 'Responsable actualizado correctamente',
            'data'    => [
                'id'              => $r->id,
                'nombre_completo' => $r->nombre_completo,
                'ci'              => $r->ci,
                'email'           => $r->email,
                'telefono'        => $r->telefono,
            ]
        ], 200);
    }

    /**
     * Buscar responsable por CI (texto plano). 
     * Como “ci” está cifrado en la BD, iteramos desencriptando hasta encontrar coincidencia.
     */
    public function showByCi($ci)
    {
        $responsable = Responsable::all()->first(function ($r) use ($ci) {
            try {
                return Crypt::decryptString($r->getRawOriginal('ci')) === $ci;
            } catch (\Exception $e) {
                return false;
            }
        });

        if (!$responsable) {
            return response()->json([
                'error' => 'Responsable no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'              => $responsable->id,
                'ci'              => $responsable->ci,
                'nombre_completo' => $responsable->nombre_completo,
                'email'           => $responsable->email,
                'telefono'        => $responsable->telefono,
            ]
        ], 200);
    }
}
