<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grado;
use App\Models\Seccion;

class GradoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allSecciones(Request $request)
    {
        return Seccion::where('estado', 'ACTIVO')->get();
    }

    public function index(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $search = $request->input('search', '');

        // Filtrar los usuarios según el parámetro de búsqueda
        $secciones = Grado::with('seccion')
                            ->where('estado', 'ACTIVO')
                            ->where('grado', 'like', "%{$search}%")
                            ->paginate(10);

        return response()->json($secciones);
    }

    public function gradosList()
    {
        $grado = Grado::with('seccion')->where('estado','ACTIVO')->get();

        return response()->json($grado);
    }

    public function showGradoXturnoCiclo1(Request $request)
    {
        $turno = $request->query('turno'); 

        if (!in_array($turno, ['MAÑANA', 'TARDE'])) {
            return response()->json(['error' => 'Turno inválido'], 400);
        }

        $grado = Grado::with('seccion')
            ->where('estado', 'ACTIVO')
            ->where('turno', $turno)
            ->whereIn('grado', ['Segundo', 'Tercero'])
            ->get();


        return response()->json($grado);
    }

    public function showGradoXturnoCiclo2(Request $request)
    {
        $turno = $request->query('turno'); 

        if (!in_array($turno, ['MAÑANA', 'TARDE'])) {
            return response()->json(['error' => 'Turno inválido'], 400);
        }

        $grado = Grado::with('seccion')
            ->where('estado', 'ACTIVO')
            ->where('turno', $turno)
            ->whereIn('grado', ['Cuarto', 'Quinto', 'Sexto'])
            ->get();


        return response()->json($grado);
    }

    public function showGradoXturnoCiclo3(Request $request)
    {
        $turno = $request->query('turno'); 

        if (!in_array($turno, ['MAÑANA', 'TARDE'])) {
            return response()->json(['error' => 'Turno inválido'], 400);
        }

        $grado = Grado::with('seccion')
            ->where('estado', 'ACTIVO')
            ->where('turno', $turno)
            ->whereIn('grado', ['Septimo', 'Octavo', 'Noveno'])
            ->get();


        return response()->json($grado);
    }

    public function showGradoXturnoCiclo4(Request $request)
    {
        $turno = $request->query('turno'); 

        if (!in_array($turno, ['MAÑANA', 'TARDE'])) {
            return response()->json(['error' => 'Turno inválido'], 400);
        }

        $grado = Grado::with('seccion')
            ->where('estado', 'ACTIVO')
            ->where('turno', $turno)
            ->where('grado', 'like', '%Bachillerato%')
            ->get();


        return response()->json($grado);
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
            'grado' => 'required|string|max:100',
            'id_seccion' => 'required|exists:Seccion,id_seccion',
            'cantidad_alumnos' => 'nullable|integer|min:0',
            'estado' => 'nullable|in:ACTIVO,INACTIVO',
        ]);

        $grado = Grado::create([
            'grado' => $validated['grado'],
            'id_seccion' => $validated['id_seccion'],
            'cantidad_alumnos' => $validated['cantidad_alumnos'] ?? 0,
            'estado' => $validated['estado'] ?? 'ACTIVO',
        ]);

        return response()->json([
            'message' => 'Grado creado correctamente',
            'data' => $grado
        ], 201);
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
         $grado = Grado::find($id);

        if (!$grado) {
            return response()->json(['message' => 'Grado no encontrado'], 404);
        }

        $validated = $request->validate([
            'grado' => 'required|string|max:100',
            'id_seccion' => 'required|exists:Seccion,id_seccion',
            'cantidad_alumnos' => 'nullable|integer|min:0',
            'estado' => 'required|in:ACTIVO,INACTIVO',
        ]);

        $grado->update($validated);

        return response()->json([
            'message' => 'Grado actualizado correctamente',
            'data' => $grado
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $grado = Grado::find($id);

        if (!$grado) {
            return response()->json(['message' => 'Grado no encontrado'], 404);
        }

        $grado->estado = 'INACTIVO';
        $grado->save();

        return response()->json(['message' => 'Grado eliminado correctamente']);
    }
}
