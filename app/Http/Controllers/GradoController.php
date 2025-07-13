<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Grado;
use App\Models\Seccion;
use Illuminate\Support\Facades\DB;

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

    public function allGrados()
    {
        $ordenGrados = [
            'Primero' => 1,
            'Segundo' => 2,
            'Tercero' => 3,
            'Cuarto' => 4,
            'Quinto' => 5,
            'Sexto' => 6,
            'Septimo' => 7,
            'Octavo' => 8,
            'Noveno' => 9,
            '1er Bachillerato' => 10,
            '2do Bachillerato' => 11
        ];

        $grados = Grado::with('seccion')
            ->where('estado', 'ACTIVO')
            ->get()
            ->sortBy(function ($grado) use ($ordenGrados) {
                return $ordenGrados[$grado->grado] ?? 99;
            })
            ->values();

        return response()->json($grados);
    }

    public function allGradosByID(Request $request)
    {
        $id_persona = $request->input('id_persona');
        $estado = 'ACTIVO';

        $grados = DB::table('Persona as p')
            ->join('Docente as d', 'p.id_persona', '=', 'd.id_persona')
            ->join('Docente_Materia_Grado as dmg', 'd.id_docente', '=', 'dmg.id_docente')
            ->join('Grado as g', 'g.id_grado', '=', 'dmg.id_grado')
            ->join('Seccion as s', 's.id_seccion', '=', 'g.id_seccion')
            ->whereYear('dmg.fecha_asignacion', now()->year)
            ->where('p.id_persona', $id_persona)
            ->where('d.estado', $estado)
            ->where('dmg.estado', $estado)
            ->where('g.estado', $estado)
            ->where('s.estado', $estado)
            ->select('g.*', 's.*')
            ->distinct()
            ->orderByRaw("
                CASE 
                    WHEN g.grado = 'Primero' THEN 1
                    WHEN g.grado = 'Segundo' THEN 2
                    WHEN g.grado = 'Tercero' THEN 3
                    WHEN g.grado = 'Cuarto' THEN 4
                    WHEN g.grado = 'Quinto' THEN 5
                    WHEN g.grado = 'Sexto' THEN 6
                    WHEN g.grado = 'Séptimo' THEN 7
                    WHEN g.grado = 'Octavo' THEN 8
                    WHEN g.grado = 'Noveno' THEN 9
                    WHEN g.grado = '1er Bachillerato' THEN 10
                    WHEN g.grado = '2do Bachillerato' THEN 11
                    ELSE 99
                END
            ")
            ->get();

        return response()->json($grados);
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
        $ordenGrados = [
            'Primero' => 1,
            'Segundo' => 2,
            'Tercero' => 3,
            'Cuarto' => 4,
            'Quinto' => 5,
            'Sexto' => 6,
            'Septimo' => 7,
            'Octavo' => 8,
            'Noveno' => 9,
            '1er Bachillerato' => 10,
            '2do Bachillerato' => 11,
        ];

        $grados = Grado::with('seccion')
            ->where('estado', 'ACTIVO')
            ->get()
            ->sortBy(function ($grado) use ($ordenGrados) {
                return $ordenGrados[$grado->grado] ?? 99;
            })
            ->values();

        return response()->json($grados);
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
            ->whereIn('grado', ['Primero','Segundo', 'Tercero'])
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
            'turno' => 'required|in:MAÑANA,TARDE',
        ]);

        $grado = Grado::create([
            'grado' => $validated['grado'],
            'id_seccion' => $validated['id_seccion'],
            'cantidad_alumnos' => $validated['cantidad_alumnos'] ?? 0,
            'estado' => $validated['estado'] ?? 'ACTIVO',
            'turno' => $validated['turno'],
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
            'turno' => 'required|in:MAÑANA,TARDE'
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
