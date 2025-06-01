<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Materia;
use App\Models\Estudiante;
use App\Models\HistorialEstudiante;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotaController extends Controller
{
    public function allNotas(){
        return Nota::all();
    }

    public function getFormularioData()
    {
        $estudiantes = Estudiante::with('persona')->get()->map(function ($est) {
            return [
                'id_estudiante' => $est->id_estudiante,
                'nombre' => $est->persona->nombre ?? '',
                'apellido' => $est->persona->apellido ?? '',
            ];
        });

        return response()->json([
            'materias' => Materia::all(),
            'estudiantes' => $estudiantes,
        ]);
    }


    // Obtener todas las notas con información de historial
    // En tu controlador de notas (ej: NotaController.php)
    public function index(Request $request)
    {
        $query = Nota::with(['historial.estudiante.persona', 'materia']) // relación anidada
            ->join('historial', 'notas.id_historial', '=', 'historial.id')
            ->join('estudiantes', 'historial.estudiante_id', '=', 'estudiantes.id')
            ->join('personas', 'estudiantes.id_persona', '=', 'personas.id')
            ->select('notas.*', 'estudiantes.id as estudiante_id', 'personas.nombre as nombre_estudiante', 'personas.apellido as apellido_estudiante')
            ->paginate(10);

        return response()->json($query);
    }


    // Mostrar formulario: obtener historial para dropdown
    public function create()
    {
        $historiales = HistorialEstudiante::with('estudiante', 'materia')->get();
        return response()->json($historiales); // esto lo usas para poblar el <select>
    }

    // // Guardar una nueva nota
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'historial_estudiante_id' => 'required|exists:historial_estudiantes,id',
    //         'valor' => 'required|numeric|min:0|max:100',
    //         'observacion' => 'nullable|string|max:255',
    //     ]);

    //     $nota = Nota::create($validated);

    //     return response()->json(['message' => 'Nota creada con éxito', 'nota' => $nota], 201);
    // }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'estudiante_id' => 'required|exists:estudiantes,id',
    //         'materia_id' => 'required|exists:materias,id',
    //         'valor' => 'required|numeric|min:0|max:100',
    //         'observacion' => 'nullable|string|max:255',
    //     ]);

    //     $añoActual = Carbon::now()->year;

    //     $historial = HistorialEstudiante::where('estudiante_id', $validated['estudiante_id'])
    //                     ->whereYear('anio', $añoActual)
    //                     ->where('materia_id', $validated['materia_id'])
    //                     ->first();

    //     if (!$historial) {
    //         return response()->json(['error' => 'No se encontró historial para este estudiante en el año actual.'], 404);
    //     }

    //     $nota = Nota::create([
    //         'historial_estudiante_id' => $historial->id,
    //         'valor' => $validated['valor'],
    //         'observacion' => $validated['observacion'] ?? null,
    //     ]);

    //     return response()->json(['message' => 'Nota creada con éxito', 'nota' => $nota], 201);
    // }


    



public function store(Request $request)
{
    // Validación básica
    // $request->validate([
    //     'id_estudiante' => 'required|exists:estudiantes,id_estudiante',
    //     'id_grado' => 'required|exists:grados,id_grado',
    //     'id_materia' => 'required|exists:materias,id_materia',
    //     'id_seccion' => 'required|exists:secciones,id_seccion',
    //     'id_periodo' => 'required|exists:periodos,id_periodo',
    //     'actividad1' => 'nullable|numeric|min:0|max:20',
    //     'actividad2' => 'nullable|numeric|min:0|max:20',
    //     'actividad3' => 'nullable|numeric|min:0|max:20',
    //     'actividadInt' => 'nullable|numeric|min:0|max:20',
    //     'examen' => 'nullable|numeric|min:0|max:20',
    // ]);

    // Buscar historial o crearlo si no existe
    $historial = HistorialEstudiante::firstOrCreate(
        [
            'id_estudiante' => $request->id_estudiante,
            'id_grado' => $request->id_grado,
        ],
        [
            'anio' => now()->year,
            'estado' => 'CURSANDO'
        ]
    );

    // Verificar si ya existe una nota con la misma materia y periodo
    $existeNota = Nota::where('id_historial', $historial->id_historial)
        ->where('id_materia', $request->id_materia)
        ->where('id_periodo', $request->id_periodo)
        ->exists();

    if ($existeNota) {
        return response()->json([
            'message' => 'Ya existe una nota registrada para este estudiante en la materia y periodo especificado.'
        ], 409);
    }

    // $promedio = collect([
    //     $request->actividad1,
    //     $request->actividad2,
    //     $request->actividad3,
    //     $request->actividadInt,
    //     $request->examen
    // ])->filter()->avg();

    $nota = Nota::create([
        'id_historial' => $historial->id_historial,
        'id_materia' => $request->id_materia,
        'id_periodo' => $request->id_periodo,
        'actividad1' => $request->actividad1,
        'actividad2' => $request->actividad2,
        'actividad3' => $request->actividad3,
        'actividadInt' => $request->actividadInt,
        'examen' => $request->examen,
        'id_periodo' => $request->id_periodo,
    ]);

    return response()->json([
        'message' => 'Nota creada exitosamente',
        // 'nota' => $nota
        'historial' => $historial,
        'existeNota' => $existeNota,
        'id_periodo' => $request->id_periodo,
    ], 200);
}










    // Obtener una nota específica
    public function show($id)
    {
        $nota = Nota::with('historialEstudiante')->findOrFail($id);
        return response()->json($nota);
    }

    // Actualizar una nota
    // public function update(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         'historial_estudiante_id' => 'required|exists:historial_estudiantes,id',
    //         'valor' => 'required|numeric|min:0|max:100',
    //         'observacion' => 'nullable|string|max:255',
    //     ]);

    //     $nota = Nota::findOrFail($id);
    //     $nota->update($validated);

    //     return response()->json(['message' => 'Nota actualizada con éxito', 'nota' => $nota]);
    // }

   public function update(Request $request, $id)
{
    $validated = $request->validate([
        'actividad1' => 'required|numeric|min:0|max:20',
        'actividad2' => 'required|numeric|min:0|max:20',
        'actividad3' => 'required|numeric|min:0|max:20',
        'actividadInt' => 'required|numeric|min:0|max:20',
        'examen' => 'required|numeric|min:0|max:20',
    ]);

    $nota = Nota::where('id_nota', $id)->firstOrFail();

    // $validated['promedio'] = (
    //     $validated['actividad1'] +
    //     $validated['actividad2'] +
    //     $validated['actividad3'] +
    //     $validated['actividadInt'] +
    //     $validated['examen']
    // ) / 5;

    $nota->update($validated);

    return response()->json(['message' => 'Nota actualizada correctamente', 'nota' => $nota]);
}



    // Eliminar una nota
    public function destroy($id)
    {
        $nota = Nota::findOrFail($id);
        $nota->delete();

        return response()->json(['message' => 'Nota eliminada']);
    }
}
