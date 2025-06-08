<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Estudiante;
use App\Models\HistorialEstudiante;
use App\Models\Nota;
use App\Models\Periodo;
use App\Models\Seccion;
use App\Models\Docente;
use App\Models\DocenteMateriaGrado;
use App\Models\Grado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allEstudiantes()
    {
        return Estudiante::all();
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $estudiantes = Estudiante::with('persona')
            ->where('estado', 'ACTIVO')
            ->where(function ($query) use ($search) {
                $query->whereHas('persona', function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%");
                })
                    ->orWhere('nie', 'like', "%{$search}%");
            })
            ->paginate(10);

        return response()->json($estudiantes);
    }


    public function estudiantesByResponsable(Request $request)
    {
        $responsable = $request->input('responsable');

        if (!$responsable) {
            return response()->json(['error' => 'Responsable es obligatorio'], 400);
        }

        $anioActual = date('Y');

        $estudiantes = Estudiante::with([
            'responsableEstudiantes.responsable',
            'responsableEstudiantes.estudiante',
            'persona',
            'historialEstudianteActual'
        ])
            ->where('estado', 'ACTIVO')
            ->whereHas('responsableEstudiantes.responsable', function ($query) use ($responsable) {
                $query->where('id_persona', $responsable);
            })
            ->whereHas('historialEstudianteActual')
            ->get();

        return response()->json($estudiantes);
    }


    public function searchSeccion($idSeccion)
    {
        $historiales = HistorialEstudiante::whereHas('grado.seccion', function ($query) use ($idSeccion) {
            $query->where('id_seccion', $idSeccion);
        })
        ->with(['estudiante.persona'])
        ->get();

        $estudiantes = $historiales->map(function($historial) {
        // Buscar notas que coincidan con el historial actual
        $notas = Nota::where('id_historial', $historial->id_historial)->get();
        
            return [
                'estudiante' => $historial->estudiante,
                'notas' => $notas->map(function($nota) {
                    return [
                        'id_nota' => $nota,
                        'periodo' => $periodo = Periodo::where('id_periodo', $nota->id_periodo)->first(),
                    ];
                }),
            ];
        })->unique('estudiante.id_estudiante')->values();

        return response()->json([
            'seccion_id' => $idSeccion,
            'total_estudiantes' => $estudiantes->count(),
            'estudiantes' => $estudiantes
        ]);
    }









public function seccionesPorUsuario($idRol, $idPersona)
{
    if (!in_array($idRol, [1, 2])) {
        return response()->json(['error' => 'No autorizado. Rol no permitido.'], 403);
    }

    // =================== ADMINISTRADOR ===================
    if ($idRol == 1) {
        $secciones = Seccion::all();
        return response()->json([
            'rol' => 'ADMINISTRADOR',
            'total_secciones' => $secciones->count(),
            'secciones' => $secciones
        ]);
    }

    // =================== DOCENTE ===================
    $docente = Docente::where('id_persona', $idPersona)->first();
    if (!$docente) {
        return response()->json(['error' => 'El usuario no está registrado como docente.'], 404);
    }

    // Obtener asignaciones del docente
    $asignaciones = DocenteMateriaGrado::where('id_docente', $docente->id_docente)
        ->with(['materia', 'grado.seccion'])
        ->get();

    if ($asignaciones->isEmpty()) {
        return response()->json(['message' => 'El docente no tiene asignaciones registradas.'], 404);
    }

    $secciones = collect();
    $grados = collect();
    $materias = collect();
    // $estudiantesConNotas = collect();

    foreach ($asignaciones as $asignacion) {
        $grado = $asignacion->grado;
        $seccion = $grado->seccion;
        $materia = $asignacion->materia;

        // Acumular secciones, grados y materias únicas
        $secciones->push($seccion);
        $grados->push([
            'id_grado' => $grado->id_grado,
            'grado' => $grado->grado,
            'id_seccion' => $grado->id_seccion,
            'seccion' => $seccion->seccion,
        ]);
        $materias->push([
            'id_materia' => $materia->id_materia,
            'nombre_materia' => $materia->nombre_materia
        ]);

        // // Obtener estudiantes del grado
        // $historiales = HistorialEstudiante::where('id_grado', $grado->id_grado)
        //     ->with(['estudiante.persona'])
        //     ->get();

        // foreach ($historiales as $historial) {
        //     $notas = Nota::where('id_historial', $historial->id_historial)
        //         ->where('id_materia', $materia->id_materia)
        //         // ->with(['periodo'])
        //         ->get();

        //     $estudiantesConNotas->push([
        //         'estudiante' => [
        //             'id_estudiante' => $historial->estudiante->id_estudiante,
        //             'nombre' => $historial->estudiante->persona->nombre,
        //             'apellido' => $historial->estudiante->persona->apellido,
        //             'genero' => $historial->estudiante->persona->genero,
        //             'telefono' => $historial->estudiante->persona->telefono,
        //             'direccion' => $historial->estudiante->persona->direccion,
        //         ],
        //         'grado' => $grado->grado,
        //         'seccion' => $seccion->seccion,
        //         'materia' => $materia->nombre_materia,
        //         'notas' => $notas->map(function ($nota) {                    
        //             $periodo = Periodo::where('id_periodo', $nota->id_periodo)->get();
        //             return [
        //                 'id_nota' => $nota->id_nota,
        //                 'actividad1' => $nota->actividad1,
        //                 'actividad2' => $nota->actividad2,
        //                 'actividad3' => $nota->actividad3,
        //                 'actividadInt' => $nota->actividadInt,
        //                 'examen' => $nota->examen,
        //                 'promedio' => $nota->promedio,
        //                 'periodos' => $nota->periodo ? [
        //                     'id_periodo' => $nota->periodo->id_periodo,
        //                     'periodo' => $nota->periodo->periodo,
        //                     'estado' => $nota->periodo->estado,
        //                 ] : null
        //             ];
        //         })
        //     ]);
        // }
    }

    return response()->json([
        'rol' => 'DOCENTE',
        'total_secciones' => $secciones->unique('id_seccion')->count(),
        'secciones' => $secciones->unique('id_seccion')->values(),
        'grados' => $grados->unique('id_grado')->values(),
        'materias' => $materias->unique('id_materia')->values()//,
        // 'estudiantes' => $estudiantesConNotas
        //     ->unique(fn($e) => $e['estudiante']['id_estudiante'] . '-' . $e['materia'])
        //     ->values()
    ]);
}

public function estudiantesConNotasFiltrados($id_grado, $id_materia, $id_seccion)
{
    // Obtener los historiales de estudiantes filtrados por grado y sección
    $historiales = HistorialEstudiante::where('id_grado', $id_grado)
        ->whereHas('grado.seccion', function($query) use ($id_seccion) {
            $query->where('id_seccion', $id_seccion);
        })
        ->with(['estudiante.persona'])
        ->get();

    $estudiantes = $historiales->map(function($historial) use ($id_materia) {
        // Obtener notas del historial y materia especificados
        $notas = Nota::where('id_historial', $historial->id_historial)
            ->where('id_materia', $id_materia)
            ->with('periodo') // relación periodo en modelo Nota
            ->get();

        return [
            'estudiante' => [
                'id_estudiante' => $historial->estudiante->id_estudiante,
                'nombre' => $historial->estudiante->persona->nombre,
                'apellido' => $historial->estudiante->persona->apellido,
                // Agrega otros datos si quieres
            ],
            'notas' => $notas->map(function($nota) {
                return [
                    'id_nota' => $nota->id_nota,
                    'actividad1' => $nota->actividad1,
                    'actividad2' => $nota->actividad2,
                    'actividad3' => $nota->actividad3,
                    'actividadInt' => $nota->actividadInt,
                    'examen' => $nota->examen,
                    'promedio' => $nota->promedio,
                    'periodo' => $nota->periodo ? [
                        'id_periodo' => $nota->periodo->id_periodo,
                        'periodo' => $nota->periodo->periodo,
                        'estado' => $nota->periodo->estado,
                    ] : null,
                ];
            }),
        ];
    })->values();

    return response()->json([
        'id_grado' => $id_grado,
        'id_materia' => $id_materia,
        'id_seccion' => $id_seccion,
        'total_estudiantes' => $estudiantes->count(),
        'estudiantes' => $estudiantes,
    ]);
}


public function estudiantesConNotasFiltradosNew($id_grado, $id_materia, $id_seccion)
{
    // Obtener historiales filtrados por grado y sección
    $historiales = HistorialEstudiante::where('id_grado', $id_grado)
        ->whereHas('grado.seccion', function($query) use ($id_seccion) {
            $query->where('id_seccion', $id_seccion);
        })
        ->with(['estudiante.persona'])
        ->get();

    // Filtrar solo estudiantes que NO tienen notas registradas para esa materia
    $estudiantes = $historiales->filter(function ($historial) use ($id_materia) {
        return Nota::where('id_historial', $historial->id_historial)
            ->where('id_materia', $id_materia)
            ->doesntExist();
    })->map(function ($historial) {
        return [
            'estudiante' => [
                'id_estudiante' => $historial->estudiante->id_estudiante,
                'nombre' => $historial->estudiante->persona->nombre,
                'apellido' => $historial->estudiante->persona->apellido,
            ],
        ];
    })->values();

    return response()->json([
        'estudiantes' => $estudiantes,
    ]);
}











    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validación
        $rules = [
            'nombre'    => 'required|string|max:50',
            'apellido'  => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:15',
            'genero'    => 'required|in:MASCULINO,FEMENINO,OTRO',
        ];

        $rules['correo'] = 'required|email|max:50';
        $rules['nie'] = 'required|string|max:10';


        $validated = Validator::make($request->all(), $rules)->validate();

        DB::beginTransaction();

        try {
            // Crear la persona
            $persona = Persona::create([
                'nombre'    => $validated['nombre'],
                'apellido'  => $validated['apellido'],
                'direccion' => $validated['direccion'] ?? null,
                'telefono'  => $validated['telefono'] ?? null,
                'genero'    => $validated['genero'],
            ]);

            $usuario = null;

            Estudiante::create([
                'id_persona' => $persona->id_persona,
                'correo'     => $validated['correo'],
                'estado'     => 'ACTIVO',
                'nie'        => $validated['nie'],
            ]);


            DB::commit();

            return response()->json([
                'message' => 'Estudiante creado correctamente',
                'data' => $usuario
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear usuario',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   public function show($id_historial)
    {
        // Buscar el estudiante por su ID (id_historial)
        $historial = HistorialEstudiante::with('estudiante.persona')->find($id_historial);
        $estudiante = Estudiante::with('persona')->find($id_historial);
        $persona = Persona::find($estudiante->id_persona);

        // Verificar si se encontró el estudiante
        if (!$estudiante) {
            return response()->json(['error' => 'Estudiante no encontrado'], 404);
        }

        return response()->json([
            'historial' => $historial,
            'estudiantes' => $estudiante,
            'persona' => $persona,
        ]);
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
        // Validación
        $rules = [
            'nombre'    => 'required|string|max:50',
            'apellido'  => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:15',
            'genero'    => 'required|in:MASCULINO,FEMENINO,OTRO',
            'correo'    => 'required|email|max:50',
            'nie'       => 'required|string|max:10',
        ];

        $validated = Validator::make($request->all(), $rules)->validate();

        DB::beginTransaction();

        try {
            // Buscar el estudiante
            $estudiante = Estudiante::with('persona')->findOrFail($id);

            // Validar que el NIE no esté duplicado (excepto el mismo registro)
            $existeNie = Estudiante::where('nie', $validated['nie'])
                ->where('id_estudiante', '!=', $estudiante->id_estudiante)
                ->exists();

            if ($existeNie) {
                return response()->json([
                    'error' => 'El número de NIE ya está registrado por otro estudiante.'
                ], 422);
            }

            // Actualizar persona
            $estudiante->persona->update([
                'nombre'    => $validated['nombre'],
                'apellido'  => $validated['apellido'],
                'direccion' => $validated['direccion'] ?? null,
                'telefono'  => $validated['telefono'] ?? null,
                'genero'    => $validated['genero'],
            ]);

            // Actualizar estudiante
            $estudiante->update([
                'correo' => $validated['correo'],
                'nie'    => $validated['nie'],
                // 'estado' => opcional si también se permite cambiar estado
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Estudiante actualizado correctamente',
                'data' => $estudiante
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al actualizar el estudiante',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $estudiantes = Estudiante::find($id);

        if (!$estudiantes) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        $estudiantes->estado = 'INACTIVO';
        $estudiantes->save();

        return response()->json(['message' => 'Estudiante eliminado correctamente']);
    }
}
