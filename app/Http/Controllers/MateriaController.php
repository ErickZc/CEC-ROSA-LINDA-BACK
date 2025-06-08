<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Materia;

class MateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allMaterias(){
        return Materia::all();
    }

    public function index(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $search = $request->input('search', '');

        // Filtrar los usuarios según el parámetro de búsqueda
        $materias = Materia::where('nombre_materia', 'like', "%{$search}%")
                            ->paginate(10);

        // Devolver los usuarios paginados
        return response()->json($materias);
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
            'nombre_materia' => 'required|string|max:50|unique:Materia,nombre_materia',
            'estado' => 'nullable|in:ACTIVO,INACTIVO',
            'id_ciclo' => 'required|exists:Ciclo,id_ciclo',
        ]);

        $materia = Materia::create([
            'nombre_materia' => $validated['nombre_materia'],
            'estado' => $validated['estado'] ?? 'ACTIVO',
            'id_ciclo' => $validated['id_ciclo']
        ]);

        return response()->json([
            'message' => 'Materia creada correctamente',
            'data' => $materia
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
    $materia = Materia::find($id);
        if (!$materia) {
            return response()->json(['error' => 'No encontrado'], 404);
        }
        return response()->json($materia);
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
        // Buscar la materia
        $materia = Materia::find($id);
        if (!$materia) {
            return response()->json(['message' => 'Materia no encontrada'], 404);
        }

        // Validar datos
        $validated = $request->validate([
            'nombre_materia' => 'required|string|max:50',
            'estado' => 'required|in:ACTIVO,INACTIVO',
            'id_ciclo' => 'required|exists:Ciclo,id_ciclo',
        ]);

        // Asignar valores
        $materia->nombre_materia = $validated['nombre_materia'];
        $materia->estado = $validated['estado'];
        $materia->id_ciclo = $validated['id_ciclo'];

        // Guardar cambios
        $materia->save();

        return response()->json([
            'message' => 'Materia actualizada correctamente',
            'data' => $materia
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $materia = Materia::find($id);

        if (!$materia) {
            return response()->json(['message' => 'Materia no encontrada'], 404);
        }

        $materia->delete();

        return response()->json(['message' => 'Materia eliminada correctamente']);
    }
}
