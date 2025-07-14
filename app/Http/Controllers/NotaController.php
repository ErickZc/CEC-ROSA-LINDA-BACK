<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Nota;
use App\Models\Materia;
use App\Models\Estudiante;
use App\Models\Responsable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\HistorialEstudiante;
use App\Models\ResponsableEstudiante;
use Illuminate\Pagination\LengthAwarePaginator;

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
            'anio' => 'required|numeric'
        ]);

        $validated['fecha'] = Carbon::now();   

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
        
        // Cargar relación periodo
        $nota->load('periodo');

        return response()->json([
            'message' => $mensaje,
            'nota' => $nota
        ], 200);
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

    public function mostrarNotasPorResponsable(Request $request)
    {
        $id_grado = $request->input('id_grado');
        $id_periodo = $request->input('id_periodo');
        $anio = $request->input('anio', date('Y'));
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);
        $id_persona = $request->input('id_persona');

        if (!$id_persona) {
            return response()->json(['message' => 'Debe proporcionar el ID de la persona responsable'], 400);
        }

        // Buscar al responsable
        $responsable = Responsable::where('id_persona', $id_persona)->first();
        if (!$responsable) {
            return response()->json(['message' => 'No se encontró el responsable'], 404);
        }

        // Obtener estudiantes asignados a ese responsable
        $relaciones = ResponsableEstudiante::where('id_responsable', $responsable->id_responsable)
            ->where('estado', 'ACTIVO')
            ->pluck('id_estudiante');

        if ($relaciones->isEmpty()) {
            return response()->json(['message' => 'No hay estudiantes asignados a este responsable'], 404);
        }

        $query = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion'])
            ->where('anio', $anio)
            ->whereIn('id_estudiante', $relaciones);
        
            if($id_grado){
                $query->where('id_grado', $id_grado);
            }

        $historiales = $query->get();

        $boletas = [];

        foreach ($historiales as $historial) {
            $notas = $historial->notas()->with('materia.ciclo')->get()
                ->groupBy(fn ($item) => $item->materia->nombre_materia ?? 'Sin nombre');

            $primerNota = $notas->first()?->first();
            $cicloNombre = $primerNota?->materia?->ciclo?->nombre ?? '';
            $isBachillerato = Str::contains(strtolower($cicloNombre), 'bachillerato');

            foreach ($notas as $materia => $registros) {
                if($id_periodo){
                    $registrosPeriodo = $registros->firstWhere('id_periodo', $id_periodo);
                    if($registrosPeriodo){
                        $boletas[] = [
                            'id_estudiante' => $historial->estudiante->id_estudiante,
                            'nie' => $historial->estudiante->nie,
                            'nombre' => $historial->estudiante->persona->apellido . ', ' . $historial->estudiante->persona->nombre,
                            'materia' => $materia,
                            'actividad1' => $registrosPeriodo->actividad1,
                            'actividad2' => $registrosPeriodo->actividad2,
                            'actividad3' => $registrosPeriodo->actividad3,
                            'actividadInt' => $registrosPeriodo->actividadInt,
                            'examen' => $registrosPeriodo->examen,
                            'promedio' => $registrosPeriodo->promedio,
                        ];
                    }
                    continue;
                }
            }
        }

        // Paginación manual
        $offset = ($currentPage - 1) * $perPage;
        $itemsForPage = array_slice($boletas, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $itemsForPage,
            count($boletas),
            $perPage,
            $currentPage,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return response()->json($paginator);
    }

    public function obtenerGradosPorResponsable(Request $request)
    {
        $id_persona = $request->input('id_persona');
        $anio = $request->input('anio', date('Y'));

        if (!$id_persona) {
            return response()->json(['message' => 'Debe proporcionar el ID de la persona responsable'], 400);
        }

        $responsable = Responsable::where('id_persona', $id_persona)->first();
        if (!$responsable) {
            return response()->json(['message' => 'Responsable no encontrado'], 404);
        }

        // Obtener los estudiantes asignados
        $idEstudiantes = ResponsableEstudiante::where('id_responsable', $responsable->id_responsable)
            ->where('estado', 'ACTIVO')
            ->pluck('id_estudiante');

        if ($idEstudiantes->isEmpty()) {
            return response()->json([]);
        }

        // Obtener los grados únicos de esos estudiantes en el año solicitado
        $grados = HistorialEstudiante::with('grado.seccion')
            ->whereIn('id_estudiante', $idEstudiantes)
            ->where('anio', $anio)
            ->get()
            ->map(function ($historial) {
                $nombreGrado = $historial->grado->grado;
                $esBachillerato = str_contains(strtolower($nombreGrado), 'bachillerato');
                return [
                    'id_grado' => $historial->grado->id_grado,
                    'grado' => $nombreGrado,
                    'seccion' => $historial->grado->seccion->seccion,
                    'turno' => $historial->grado->turno,
                    'es_bachillerato' => $esBachillerato,
                ];
            })
            ->unique('id_grado') // eliminar duplicados por grado
            ->values();

        return response()->json($grados);
    }

    public function verificarEstudiantesAsignados(Request $request)
    {
        $id_persona = $request->input('id_persona');

        if (!$id_persona) {
            return response()->json(['message' => 'Debe proporcionar el ID del responsable'], 400);
        }

        // Buscar al responsable
        $responsable = Responsable::where('id_persona', $id_persona)->first();
        if (!$responsable) {
            return response()->json(['message' => 'Responsable no encontrado'], 404);
        }

        // Buscar estudiantes asignados activos
        $estudiantes = ResponsableEstudiante::where('id_responsable', $responsable->id_responsable)
            ->where('estado', 'ACTIVO')
            ->with(['estudiante.persona'])
            ->get();

        /*if ($estudiantes->isEmpty()) {
            return response()->json(['message' => 'No hay estudiantes asignados a este responsable'], 404);
        }*/

        return response()->json($estudiantes);
    }


}
