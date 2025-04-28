<?php

namespace App\Http\Controllers;

use App\Models\Fase;
use Illuminate\Http\Request;

class FaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fases = Fase::all();
        return response()->json($fases);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $fase = Fase::find($id);

        if (!$fase) {
            return response()->json(['error' => 'Fase no encontrada.'], 404);
        }

        return response()->json($fase);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
