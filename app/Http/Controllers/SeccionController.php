<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seccion;

class SeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function allSecciones(){
        return Seccion::all();
    }

    public function index(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $search = $request->input('search', '');

        // Filtrar los usuarios según el parámetro de búsqueda
        $secciones = Seccion::where('estado', 'ACTIVO')
                            ->where('seccion', 'like', "%{$search}%")
                            ->paginate(5);

        return response()->json($secciones);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $seccionExistente = Seccion::where('seccion', $request->input('seccion'))->where('estado', 'ACTIVO')->first();

        if ($seccionExistente) {
            return response()->json([
                'message' => 'La sección ya existe y está activa.'
            ], 422);
        }

        // Si no existe activa, permitir crearla
        $validated = $request->validate([
            'seccion' => 'required|string|max:50',
            'estado' => 'nullable|in:ACTIVO,INACTIVO'
        ]);

        $seccion = Seccion::create([
            'seccion' => $validated['seccion'],
            'estado' => $validated['estado'] ?? 'ACTIVO'
        ]);

        return response()->json([
            'message' => 'Sección creada correctamente',
            'data' => $seccion
        ]);
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
        $seccion = Seccion::find($id);
        if (!$seccion) {
            return response()->json(['message' => 'Seccion no encontrada'], 404);
        }

        // Validar datos
        $validated = $request->validate([
            'seccion' => 'required|string|max:50',
            'estado' => 'required|in:ACTIVO,INACTIVO'
        ]);

        // Asignar valores
        $seccion->seccion = $validated['seccion'];
        $seccion->estado = $validated['estado'];

        // Guardar cambios
        $seccion->save();

        return response()->json([
            'message' => 'Seccion actualizada correctamente',
            'data' => $seccion
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
        $seccion = Seccion::find($id);

        if (!$seccion) {
            return response()->json(['message' => 'Sección no encontrada'], 404);
        }

        $seccion->estado = 'INACTIVO';
        $seccion->save();

        return response()->json(['message' => 'Sección eliminada correctamente']);
    }
}
