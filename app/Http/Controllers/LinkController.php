<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $links = Link::all(); 
        return response()->json($links, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'enlace_archivo' => 'required|url|max:2048', 
            'peso_archivo' => 'required|integer|min:1',   
        ]);

        $link = Link::create([
            'enlace_archivo' => $request->enlace_archivo,
            'peso_archivo' => $request->peso_archivo,
        ]);

        return response()->json($link, 201);  
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $link = Link::find($id);  

        if (!$link) {
            return response()->json(['message' => 'Link no encontrado'], 404); 
        }

        return response()->json($link, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'enlace_archivo' => 'required|url|max:2048',
            'peso_archivo' => 'required|integer|min:1',
        ]);

        $link = Link::find($id);  

        if (!$link) {
            return response()->json(['message' => 'Link no encontrado'], 404);  
        }

        $link->update([
            'enlace_archivo' => $request->enlace_archivo,
            'peso_archivo' => $request->peso_archivo,
        ]);

        return response()->json($link, 200);  
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $link = Link::find($id); 

        if (!$link) {
            return response()->json(['message' => 'Link no encontrado'], 404);  
        }

        $link->delete();  

        return response()->json(['message' => 'Link eliminado'], 200);  
    }
}
