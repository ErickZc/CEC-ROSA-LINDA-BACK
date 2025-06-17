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

    public function store(Request $request)
    {

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

    public function update(Request $request, $id = null)
    {
        // Validar campos requeridos
        $validated = $request->validate([
            'actividad1' => 'required|numeric|min:0|max:20',
            'actividad2' => 'required|numeric|min:0|max:20',
            'actividad3' => 'required|numeric|min:0|max:20',
            'actividadInt' => 'required|numeric|min:0|max:20',
            'examen' => 'required|numeric|min:0|max:20',
            'id_estudiante' => 'required|integer',
            'id_grado' => 'required|integer', // sin exists
            'id_materia' => 'required|integer',
            'id_periodo' => 'required|integer',
            'anio' => 'required|numeric',
        ]);


        // Calcular el promedio
        // $validated['promedio'] = (
        //     $validated['actividad1'] +
        //     $validated['actividad2'] +
        //     $validated['actividad3'] +
        //     $validated['actividadInt'] +
        //     $validated['examen']
        // ) / 5;

        // Buscar o crear historial
        $historial = HistorialEstudiante::firstOrCreate(
            [
                'id_estudiante' => $validated['id_estudiante'],
                'id_grado' => $validated['id_grado'],
                'anio' => $validated['anio'],
            ],
            ['estado' => 'CURSANDO']
        );

        $validated['id_historial'] = $historial->id_historial;

        // Buscar nota por ID si se pasó, o buscar por combinación única
        $nota = $id !== null
            ? Nota::find($id)
            : Nota::where('id_historial', $validated['id_historial'])
                ->where('id_materia', $validated['id_materia'])
                ->where('id_periodo', $validated['id_periodo'])
                ->first();

        if ($nota) {
            $nota->update($validated);
            $mensaje = 'Nota actualizada correctamente';
        } else {
            $nota = Nota::create($validated);
            $mensaje = 'Nota creada correctamente';
        }

        return response()->json([
            'message' => $mensaje,
            'nota' => $nota
        ]);
        // return response()->json([
        //     'message' => 'OK'
        // ]);
    }

    // Eliminar una nota
    public function destroy($id)
    {
        $nota = Nota::findOrFail($id);
        $nota->delete();

        return response()->json(['message' => 'Nota eliminada']);
    }
}
