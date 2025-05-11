<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;

class PersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //cargar select
    public function allPersonas(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $search = $request->input('busquedaPersona', '');

        // Filtrar las personas según el parámetro de búsqueda
        $personas = Persona::where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%")
                            ->paginate(5);

        // Devolver las personas paginados
        return response()->json($personas);
        //return response()->json(Persona::all());
    }

    public function index(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $search = $request->input('search', '');

        // Filtrar las personas según el parámetro de búsqueda
        $personas = Persona::where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%")
                            ->paginate(10);

        // Devolver las personas paginados
        return response()->json($personas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:15',
            'genero' => 'required|in:MASCULINO,FEMENINO,OTRO',
        ]);

        $persona = Persona::create($validated);

        return response()->json(['mensaje' => 'Persona creada correctamente', 'persona' => $persona], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $persona = Persona::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:15',
            'genero' => 'required|in:MASCULINO,FEMENINO,OTRO',
        ]);

        $persona->update($validated);

        return response()->json(['mensaje' => 'Persona actualizada correctamente', 'persona' => $persona]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return response()->json(['message' => 'Seccion no encontrada'], 404);
        }

        $persona->delete();

        return response()->json(['message' => 'Seccion eliminada correctamente']);
    }
}
