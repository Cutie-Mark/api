<?php

namespace App\Http\Controllers;

use App\Models\Fase;
use Illuminate\Http\Request;

class FaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function listar()
    {
        $fases = Fase::all();
        return response()->json($fases);
    }

    /**
     * Display the specified resource.
     */
    public function mostrar(string $id)
    {
        $fase = Fase::find($id);

        if (!$fase) {
            return response()->json(['error' => 'Fase no encontrada.'], 404);
        }

        return response()->json($fase);
    }


}
