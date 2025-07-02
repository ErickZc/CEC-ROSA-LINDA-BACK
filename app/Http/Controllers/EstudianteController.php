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
use Illuminate\Pagination\LengthAwarePaginator;

class EstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allEstudentByPersonInfo()
    {
        $estudiantes = Estudiante::with(['persona', 'responsableEstudiantes.responsable.persona'])->get();
        return $estudiantes;
    }
    
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

    public function estudiantesByNIE(Request $request)
    {
        $nie = $request->nie;

        if (!$nie) {
            return response()->json(['error' => 'El codigo del estudiaante es obligatorio'], 400);
        }

        $anioActual = date('Y');

        $estudiantes = DB::table('Estudiante')
            ->join('Historial_Estudiante', 'Estudiante.id_estudiante', '=', 'Historial_Estudiante.id_estudiante')
            ->join('Grado', 'Historial_Estudiante.id_grado', '=', 'Grado.id_grado')
            ->join('Seccion', 'Seccion.id_seccion', '=', 'Grado.id_seccion')
            ->join('Persona', 'Estudiante.id_persona', '=', 'Persona.id_persona')
            ->select(
                'Estudiante.*',
                'Persona.nombre',
                'Persona.apellido',
                'Persona.direccion',
                'Persona.genero',
                'Historial_Estudiante.anio',
                'Historial_Estudiante.estado',
                'Grado.grado as nombre_grado',
                'Grado.turno',
                'Grado.id_grado',
                'Seccion.seccion as nombre_seccion'
            )
            ->where('Estudiante.estado', 'ACTIVO')
            ->where('Estudiante.nie', $nie)
            ->where('Historial_Estudiante.anio', $anioActual)
            ->get();


        if ($estudiantes->isEmpty()) {
            return response()->json(['message' => 'No se encontraron estudiantes con el NIE ' . $nie], 404);
        }

        return response()->json($estudiantes);
    }

    public function rendimientoEstudiantil(Request $request)
    {
        $nie = $request->nie;
        $periodo = $request->id_periodo;

        if (!$nie || !$periodo) {
            return response()->json(['error' => 'El NIE y el periodo son obligatorios'], 400);
        }

        $anioActual = date('Y');

        $resultados = DB::select("
            WITH notas_anteriores AS (
                SELECT 
                    id_materia,
                    id_historial,
                    promedio,
                    ROW_NUMBER() OVER (
                        PARTITION BY id_materia, id_historial 
                        ORDER BY id_periodo DESC
                    ) AS rn
                FROM Nota
                WHERE id_periodo < $periodo
            )
            SELECT 
                m.nombre_materia,
                n.promedio,
                COALESCE(n.examen, n.actividadInt, n.actividad3, n.actividad2, n.actividad1) AS ultima_nota,
                CASE 
                    WHEN n.actividad1 IS NULL OR n.actividad2 IS NULL OR n.actividad3 IS NULL 
                        OR n.actividadInt IS NULL OR n.examen IS NULL THEN 'En progreso'

                    WHEN g.grado LIKE '%Bachillerato%' AND n.promedio >= 6 THEN 'Aprobado'
                    WHEN g.grado NOT LIKE '%Bachillerato%' AND n.promedio >= 5 THEN 'Aprobado'
                    ELSE 'Reprobado'
                END AS estado,
                CASE 
                    WHEN na.promedio IS NULL THEN 
                        CASE 
                            WHEN n.promedio IS NULL THEN 'Sin datos anteriores'
                            WHEN n.promedio < 5 THEN 'El estudiante necesita mejorar en clases'
                            WHEN n.promedio < 8 THEN 'El rendimiento actual del estudiante es aceptable'
                            WHEN n.promedio >= 8 THEN 'El estudiante muestra un excelente rendimiento'
                            ELSE 'Faltan datos para determinar el rendimiento'
                        END
                    WHEN n.promedio < 5 THEN 'El estudiante tiene alta probabilidad de dejar la materia'
                    WHEN n.promedio >= 5 AND n.promedio <= 6 THEN 'El estudiante se encuentra al limite de aprobar o reprobar'
                    WHEN n.promedio <= na.promedio - 2 THEN 'Descenso grave respecto al ciclo anterior'
                    WHEN n.promedio - na.promedio > 0.5 THEN 'El estudiante ha mejorado su promedio, felicidades'
                    WHEN na.promedio - n.promedio > 0.5 THEN 'El estudiante ha bajado su rendimiento en la materia'
                    ELSE 'El estudiante sigue con la tendencia del ciclo anterior'
                END AS tendencia
            FROM Nota n
            INNER JOIN Materia m ON n.id_materia = m.id_materia
            INNER JOIN Historial_Estudiante he ON he.id_historial = n.id_historial
            INNER JOIN Estudiante e ON e.id_estudiante = he.id_estudiante
            INNER JOIN Grado g ON g.id_grado = he.id_grado
            LEFT JOIN notas_anteriores na 
                ON na.id_materia = n.id_materia 
                AND na.id_historial = n.id_historial 
                AND na.rn = 1
            WHERE n.id_periodo = $periodo
            AND e.nie = :nie
            AND he.anio = :anio
        ", [
            'nie' => $nie,
            //'periodo' => $periodo,
            'anio' => $anioActual
        ]);

        return response()->json($resultados);
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

    // public function seccionesPorUsuario($idRol, $idPersona)
    // {
    //     if (!in_array($idRol, [1, 2])) {
    //         return response()->json(['error' => 'No autorizado. Rol no permitido.'], 403);
    //     }

    //     // =================== ADMINISTRADOR ===================
    //     if ($idRol == 1) {
    //         $asignaciones = DocenteMateriaGrado::with(['materia', 'grado.seccion'])->get();

    //         if ($asignaciones->isEmpty()) {
    //             return response()->json([
    //                 'rol' => 'ADMINISTRADOR',
    //                 'message' => 'No hay asignaciones registradas en el sistema.',
    //                 'total_secciones' => 0,
    //                 'secciones' => [],
    //                 'grados' => [],
    //                 'materias' => []
    //             ], 200);
    //         }

    //         $secciones = collect();
    //         $grados = collect();
    //         $materias = collect();

    //         foreach ($asignaciones as $asignacion) {
    //             $grado = $asignacion->grado;
    //             $seccion = $grado->seccion;
    //             $materia = $asignacion->materia;

    //             $secciones->push($seccion);
    //             $grados->push([
    //                 'id_grado' => $grado->id_grado,
    //                 'grado' => $grado->grado,
    //                 'id_seccion' => $grado->id_seccion,
    //                 'seccion' => $seccion->seccion,
    //                 'grado_seccion' => $grado->grado . ' ' . $seccion->seccion
    //             ]);
    //             $materias->push([
    //                 'id_materia' => $materia->id_materia,
    //                 'nombre_materia' => $materia->nombre_materia
    //             ]);
    //         }

    //         return response()->json([
    //             'rol' => 'ADMINISTRADOR',
    //             'total_secciones' => $secciones->unique('id_seccion')->count(),
    //             'secciones' => $secciones->unique('id_seccion')->values(),
    //             'grados' => $grados->unique('id_grado')->sortBy('grado')->values(),
    //             'materias' => $materias->unique('id_materia')->sortBy('nombre_materia')->values()
    //         ]);
    //     }

    //     // =================== DOCENTE ===================
    //     $docente = Docente::where('id_persona', $idPersona)->first();
    //     if (!$docente) {
    //         return response()->json(['error' => 'El usuario no está registrado como docente.'], 404);
    //     }

    //     // Obtener asignaciones del docente
    //     $asignaciones = DocenteMateriaGrado::where('id_docente', $docente->id_docente)
    //         ->with(['materia', 'grado.seccion'])
    //         ->get();

    //     if ($asignaciones->isEmpty()) {
    //         return response()->json([
    //             'rol' => 'DOCENTE',
    //             'message' => 'El docente no tiene asignaciones registradas.',
    //             'total_secciones' => 0,
    //             'secciones' => [],
    //             'grados' => [],
    //             'materias' => []
    //         ], 200);
    //     }

    //     $secciones = collect();
    //     $grados = collect();
    //     $materias = collect();

    //     foreach ($asignaciones as $asignacion) {
    //         $grado = $asignacion->grado;
    //         $seccion = $grado->seccion;
    //         $materia = $asignacion->materia;

    //         $secciones->push($seccion);
    //         $grados->push([
    //             'id_grado' => $grado->id_grado,
    //             'grado' => $grado->grado,
    //             'id_seccion' => $grado->id_seccion,
    //             'seccion' => $seccion->seccion,
    //             'grado_seccion' => $grado->grado . ' ' . $seccion->seccion
    //         ]);
    //         $materias->push([
    //             'id_materia' => $materia->id_materia,
    //             'nombre_materia' => $materia->nombre_materia
    //         ]);
    //     }

    //     return response()->json([
    //         'rol' => 'DOCENTE',
    //         'total_secciones' => $secciones->unique('id_seccion')->count(),
    //         'secciones' => $secciones->unique('id_seccion')->values(),
    //         'grados' => $grados->unique('id_grado')->sortBy('grado')->values(),
    //         'materias' => $materias->unique('id_materia')->sortBy('nombre_materia')->values()
    //     ]);
    // }

    public function getSecciones($idRol, $idPersona, $turno)
    {
        if (!in_array($idRol, [1, 2])) {
            return response()->json(['error' => 'No autorizado. Rol no permitido.'], 403);
        }

        // =================== ADMINISTRADOR ===================
        if ($idRol == 1) {
            $asignaciones = DocenteMateriaGrado::with(['materia', 'grado.seccion'])->get();

            if ($asignaciones->isEmpty()) {
                return response()->json([
                    'rol' => 'ADMINISTRADOR',
                    'message' => 'No hay asignaciones registradas en el sistema.',
                    'total_secciones' => 0,
                    'secciones' => [],
                    'grados' => [],
                    'materias' => []
                ], 200);
            }

            $secciones = collect();
            $grados = collect();
            $materias = collect();

            foreach ($asignaciones as $asignacion) {
                $grado = $asignacion->grado;
                $seccion = $grado->seccion;
                $materia = $asignacion->materia;

                if ($grado->turno === $turno) {
                    $secciones->push($seccion);

                    $grados->push([
                        'id_grado' => $grado->id_grado,
                        'grado' => $grado->grado,
                        'id_seccion' => $grado->id_seccion,
                        'seccion' => $seccion->seccion,
                        'grado_seccion' => $grado->grado . ' ' . $seccion->seccion,
                        'turno' => $grado->turno
                    ]);

                    $materias->push([
                        'id_materia' => $materia->id_materia,
                        'nombre_materia' => $materia->nombre_materia
                    ]);
                }
            }

            return response()->json([
                'rol' => 'ADMINISTRADOR',
                'total_secciones' => $secciones->unique('id_seccion')->count(),
                'secciones' => $secciones->unique('id_seccion')->values(),
                'grados' => $grados->unique(fn ($item) => $item['id_grado'])->sortBy('grado')->values(),
                'materias' => $materias->unique('id_materia')->sortBy('nombre_materia')->values()
            ]);
        }

        // =================== DOCENTE ===================
        $docente = Docente::where('id_persona', $idPersona)->first();
        if (!$docente) {
            return response()->json(['error' => 'El usuario no está registrado como docente.'], 404);
        }

        $asignaciones = DocenteMateriaGrado::where('id_docente', $docente->id_docente)
            ->with(['materia', 'grado.seccion'])
            ->get();

        if ($asignaciones->isEmpty()) {
            return response()->json([
                'rol' => 'DOCENTE',
                'message' => 'El docente no tiene asignaciones registradas.',
                'total_secciones' => 0,
                'secciones' => [],
                'grados' => [],
                'materias' => []
            ], 200);
        }

        $secciones = collect();
        $grados = collect();
        $materias = collect();

        foreach ($asignaciones as $asignacion) {
            $grado = $asignacion->grado;
            $seccion = $grado->seccion;
            $materia = $asignacion->materia;

            if ($grado->turno === $turno) {
                $secciones->push($seccion);

                $grados->push([
                    'id_grado' => $grado->id_grado,
                    'grado' => $grado->grado,
                    'id_seccion' => $grado->id_seccion,
                    'seccion' => $seccion->seccion,
                    'grado_seccion' => $grado->grado . ' ' . $seccion->seccion,
                    'turno' => $grado->turno
                ]);

                $materias->push([
                    'id_materia' => $materia->id_materia,
                    'nombre_materia' => $materia->nombre_materia
                ]);
            }
        }

        return response()->json([
            'rol' => 'DOCENTE',
            'total_secciones' => $secciones->unique('id_seccion')->count(),
            'secciones' => $secciones->unique('id_seccion')->values(),
            'grados' => $grados->unique(fn ($item) => $item['id_grado'])->sortBy('grado')->values(),
            'materias' => $materias->unique('id_materia')->sortBy('nombre_materia')->values()
        ]);
    }

    public function getGradoSeccionesMaterias($turno, $grado, $seccion)
    {
        // Obtener asignaciones con relaciones
        $asignaciones = DocenteMateriaGrado::with(['materia', 'grado.seccion'])->get();

        if ($asignaciones->isEmpty()) {
            return response()->json([
                'message' => 'No hay asignaciones registradas.',
                'total' => 0,
                'materias' => []
            ]);
        }

        // Filtrar materias según turno, grado y sección
        $materias = collect();

        foreach ($asignaciones as $asignacion) {
            $gradoObj = $asignacion->grado;
            $seccionObj = $gradoObj->seccion;

            if (
                $gradoObj->turno === $turno &&
                $gradoObj->grado === $grado &&
                $seccionObj->seccion === $seccion
            ) {
                $materia = $asignacion->materia;
                $materias->push([
                    'id_materia' => $materia->id_materia,
                    'nombre_materia' => $materia->nombre_materia
                ]);
            }
        }

        return response()->json([
            'message' => 'Consulta realizada correctamente.',
            'total' => $materias->count(),
            'materias' => $materias->sortBy('nombre_materia')->values()
        ]);
    }

    public function contarEstudiantesPorSeccion($id_grado)
    {
        $grado = Grado::with('seccion')->where('id_grado', $id_grado)->first();

        if (!$grado) {
            return response()->json([
                'message' => 'Grado no encontrado'
            ], 404);
        }

        // Contar estudiantes de ese grado
        $cantidad_estudiantes = HistorialEstudiante::where('id_grado', $id_grado)->count();

        return response()->json([
            'id_grado' => $grado->id_grado,
            'nombre_grado' => $grado->grado,
            'seccion' => [
                'id_seccion' => $grado->seccion->id_seccion ?? null,
                'nombre_seccion' => $grado->seccion->seccion ?? null,
                'cantidad_estudiantes' => $cantidad_estudiantes
            ]
        ], 200);
    }

    public function reporteEstudiantes($id_grado, $id_materia, $id_seccion)
    {
        $historiales = HistorialEstudiante::where('id_grado', $id_grado)
        ->whereHas('grado.seccion', function($query) use ($id_seccion) {
            $query->where('id_seccion', $id_seccion);
        })
        ->with(['estudiante.persona'])
        ->get();
        
        $estudiantes = $historiales->map(function($historial) use ($id_materia) {
            $notas = Nota::where('id_historial', $historial->id_historial)
                ->where('id_materia', $id_materia)
                ->with('periodo')
                ->get();

            $estudiante = $historial->estudiante;
            $persona = $estudiante->persona;

            return [
                'id_estudiante' => $estudiante->id_estudiante ?? null,
                'nombre' => $persona->nombre ?? 'Sin nombre',
                'apellido' => $persona->apellido ?? 'Sin apellido',
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
                })
            ];
        });

        $grafico = [
            'labels' => $estudiantes->map(function ($e) {
                $nombre = $e['nombre'] ?? 'Sin nombre';
                $apellido = $e['apellido'] ?? 'Sin apellido';
                return trim($nombre . ' ' . $apellido);
            })->toArray(),


            'datos' => $estudiantes->map(function($e) {
                // Asegura que 'notas' exista y sea una colección válida
                $promedios = collect($e['notas'] ?? [])
                    ->pluck('promedio')
                    ->filter(function ($value) {
                        return is_numeric($value); // solo valores válidos
                    });

                return $promedios->count() > 0 ? round($promedios->avg(), 2) : 0;
            })->toArray()
        ];

    return response()->json([
            'id_grado' => $id_grado,
            'id_materia' => $id_materia,
            'id_seccion' => $id_seccion,
            'total_estudiantes' => $estudiantes->count(),
            'estudiantes' => $estudiantes,
            'grafico' => $grafico
        ]);
    }

// public function estudiantesConNotasFiltrados($id_grado, $id_materia, $id_periodo)
// {
//     // Obtener grado para extraer su id_seccion
//     $grado = Grado::find($id_grado);
//     if (!$grado) {
//         return response()->json(['error' => 'Grado no encontrado.'], 404);
//     }

//     $id_seccion = $grado->id_seccion;

//     $historiales = HistorialEstudiante::where('id_grado', $id_grado)
//         ->where('estado', 'CURSANDO')
//         ->whereHas('grado', function($query) use ($id_seccion) {
//             $query->where('id_seccion', $id_seccion);
//         })
//         ->with(['estudiante.persona'])
//         ->get();

//     $estudiantes = $historiales->map(function($historial) use ($id_materia, $id_periodo) {
//         $notas = Nota::where('id_historial', $historial->id_historial)
//             ->where('id_materia', $id_materia)
//             ->with('periodo')
//             ->get();

//         $notasFiltradas = $notas->filter(function($nota) use ($id_periodo) {
//             return $nota->id_periodo == $id_periodo;
//         });

//         if ($notasFiltradas->isEmpty()) {
//             $notasFiltradas = collect([
//                 (object)[
//                     'id_nota' => null,
//                     'actividad1' => null,
//                     'actividad2' => null,
//                     'actividad3' => null,
//                     'actividadInt' => null,
//                     'examen' => null,
//                     'promedio' => null,
//                     'periodo' => null,
//                 ]
//             ]);
//         }

//         return [
//             'estudiante' => [
//                 'id_estudiante' => $historial->estudiante->id_estudiante,
//                 'nombre' => $historial->estudiante->persona->nombre,
//                 'apellido' => $historial->estudiante->persona->apellido,
//             ],
//             'notas' => $notasFiltradas->map(function($nota) {
//                 return [
//                     'id_nota' => $nota->id_nota,
//                     'actividad1' => $nota->actividad1,
//                     'actividad2' => $nota->actividad2,
//                     'actividad3' => $nota->actividad3,
//                     'actividadInt' => $nota->actividadInt,
//                     'examen' => $nota->examen,
//                     'promedio' => $nota->promedio,
//                     'periodo' => $nota->periodo ? [
//                         'id_periodo' => $nota->periodo->id_periodo,
//                         'periodo' => $nota->periodo->periodo,
//                         'estado' => $nota->periodo->estado,
//                     ] : null,
//                 ];
//             }),
//         ];
//     })->values();

//     return response()->json([
//         'id_grado' => $id_grado,
//         'id_materia' => $id_materia,
//         'id_seccion' => $id_seccion, // se devuelve aunque no se reciba como parámetro
//         'id_periodo' => $id_periodo,
//         'total_estudiantes' => $estudiantes->count(),
//         'estudiantes' => $estudiantes,
//     ]);
// }


public function estudiantesConNotasFiltrados(Request $request, $id_grado, $id_materia, $id_periodo, $turno)
{
    // Obtener el parámetro de búsqueda, si existe
    $search = $request->input('search', '');

    // Validar que el grado con ese turno exista
    $grado = Grado::where('id_grado', $id_grado)
        ->where('turno', strtoupper($turno))
        ->with('seccion')
        ->first();

    // Si no se encuentra el grado, retornar una respuesta vacía pero estructurada
    if (!$grado) {
        return response()->json([
            'id_grado' => $id_grado,
            'grado' => null,
            'id_seccion' => null,
            'seccion' => null,
            'turno' => $turno,
            'id_materia' => $id_materia,
            'id_periodo' => $id_periodo,
            'total_estudiantes' => 0,
            'pagination' => [],
            'estudiantes' => [],
            'message' => 'Grado con el turno no tiene estudiantes asociados.'
        ]);
    }

    $id_seccion = $grado->id_seccion;

    // Obtener los estudiantes paginados con filtro de búsqueda
    $historiales = HistorialEstudiante::where('id_grado', $id_grado)
        ->where('estado', 'CURSANDO')
        ->whereHas('estudiante.persona', function ($query) use ($search) {
            $query->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%");
        })
        ->with(['estudiante.persona'])
        ->paginate(10); // Paginar los resultados

    // Transformar los resultados paginados manteniendo la paginación
    $estudiantesTransformados = collect($historiales->items())->transform(function ($historial) use ($id_materia, $id_periodo) {
        $notas = Nota::where('id_historial', $historial->id_historial)
            ->where('id_materia', $id_materia)
            ->with('periodo')
            ->get();

        $notasFiltradas = $notas->filter(function ($nota) use ($id_periodo) {
            return $nota->id_periodo == $id_periodo;
        });

        if ($notasFiltradas->isEmpty()) {
            $notasFiltradas = collect([
                (object)[
                    'id_nota' => null,
                    'actividad1' => null,
                    'actividad2' => null,
                    'actividad3' => null,
                    'actividadInt' => null,
                    'examen' => null,
                    'promedio' => null,
                    'periodo' => null,
                ]
            ]);
        }

        return [
            'estudiante' => [
                'id_historial' => $historial->id_historial, // clave única para Vue
                'id_estudiante' => $historial->estudiante->id_estudiante,
                'nombre' => $historial->estudiante->persona->nombre,
                'apellido' => $historial->estudiante->persona->apellido,
            ],
            'notas' => $notasFiltradas->map(function ($nota) {
                return [
                    'id_nota' => $nota->id_nota ?? null,
                    'actividad1' => number_format($nota->actividad1 ?? 0, 2, '.', ''),
                    'actividad2' => number_format($nota->actividad2 ?? 0, 2, '.', ''),
                    'actividad3' => number_format($nota->actividad3 ?? 0, 2, '.', ''),
                    'actividadInt' => number_format($nota->actividadInt ?? 0, 2, '.', ''),
                    'examen' => number_format($nota->examen ?? 0, 2, '.', ''),
                    'promedio' => number_format($nota->promedio ?? 0, 2, '.', ''),
                    'periodo' => $nota->periodo ? [
                        'id_periodo' => $nota->periodo->id_periodo,
                        'periodo' => $nota->periodo->periodo,
                        'estado' => $nota->periodo->estado,
                    ] : null,
                ];
            }),
        ];
    });

    return response()->json([
        'id_grado' => $grado->id_grado,
        'grado' => $grado->grado,
        'id_seccion' => $grado->id_seccion,
        'seccion' => $grado->seccion->seccion,
        'turno' => $grado->turno,
        'id_materia' => $id_materia,
        'id_periodo' => $id_periodo,
        'total_estudiantes' => $historiales->total(),
        'pagination' => [
            'current_page' => $historiales->currentPage(),
            'from' => $historiales->firstItem(),
            'to' => $historiales->lastItem(),
            'total' => $historiales->total(),
            'last_page' => $historiales->lastPage(),
        ],
        'estudiantes' => $estudiantesTransformados,
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


    public function estudiantesRepetidores()
{
    // 1. Obtener los historiales de estudiantes que repiten grado (tienen estado REPROBADO y CURSANDO)
    $repetidores = HistorialEstudiante::whereIn('estado', ['REPROBADO', 'CURSANDO'])
        // ->select('id_estudiante', 'id_grado')
        // ->groupBy('id_estudiante', 'id_grado')
        // ->havingRaw('COUNT(DISTINCT estado) = 2')
        ->get();

    // 2. Traer los historiales completos de esos estudiantes y grados, con estudiante, persona y notas filtradas
    // $historiales = HistorialEstudiante::with(['estudiante.persona', 'notas.periodo'])
    //     ->whereIn(function($query) use ($repetidores) {
    //         $query->selectRaw("CONCAT(id_estudiante,'-',id_grado)")
    //             ->from('Historial_Estudiante')
    //             ->whereIn('id_estudiante', $repetidores->pluck('id_estudiante'))
    //             ->whereIn('id_grado', $repetidores->pluck('id_grado'));
    //     }, $repetidores->map(fn($r) => $r->id_estudiante . '-' . $r->id_grado)->toArray())
    //     ->get();

    // 3. Agrupar por estudiante y grado
    // $agrupados = $historiales->groupBy(function($h) {
    //     return $h->id_estudiante . '-' . $h->id_grado;
    // });

    // 4. Mapear para devolver estructura con notas reprobadas (<7)
    // $estudiantes = $agrupados->map(function($grupo) {
    //     $historial = $grupo->first();
    //     $estudiante = $historial->estudiante;
    //     $persona = $estudiante->persona;

    //     $notasReprobadas = $grupo->flatMap(function($h) {
    //         return $h->notas->filter(fn($n) => $n->promedio < 7);
    //     });

    //     return [
    //         'id_estudiante' => $estudiante->id_estudiante,
    //         'nombre' => $persona->nombre,
    //         'apellido' => $persona->apellido,
    //         'notas' => $notasReprobadas->map(function($nota) {
    //             return [
    //                 'id_nota' => $nota->id_nota,
    //                 'actividad1' => $nota->actividad1,
    //                 'actividad2' => $nota->actividad2,
    //                 'actividad3' => $nota->actividad3,
    //                 'actividadInt' => $nota->actividadInt,
    //                 'examen' => $nota->examen,
    //                 'promedio' => $nota->promedio,
    //                 'periodo' => $nota->periodo ? [
    //                     'id_periodo' => $nota->periodo->id_periodo,
    //                     'periodo' => $nota->periodo->periodo,
    //                     'estado' => $nota->periodo->estado,
    //                 ] : null,
    //             ];
    //         })->values()
    //     ];
    // })->values();

    return response()->json([
        // 'total_repetidores' => $estudiantes->count(),
        // 'estudiantes' => $estudiantes,
        'repetidores' => $repetidores
    ]);
}




    
public function estudiantesRepetidores2()
{
    // 1. Cargar todos los historiales con relaciones necesarias
    $historiales = HistorialEstudiante::with(['estudiante.persona'])->get();

    // 2. Agrupar por estudiante y grado
    $agrupados = $historiales->groupBy(function ($historial) {
        return $historial->id_estudiante . '-' . $historial->id_grado;
    });

    // 3. Filtrar solo estudiantes que repiten (REPROBADO + CURSANDO)
    $repetidores = $agrupados->filter(function ($grupo) {
        $estados = $grupo->pluck('estado')
            ->map(fn($estado) => strtoupper(trim($estado)))
            ->unique();

        return $estados->contains('REPROBADO') && $estados->contains('CURSANDO');
    });

    // 4. Mapear resultados con notas reprobadas
    $estudiantes = $repetidores->map(function ($grupo) {
        $historial = $grupo->first(); // Tomamos uno del grupo para obtener estudiante/persona
        $estudiante = $historial->estudiante;
        $persona = $estudiante->persona;

        // Obtener todas las notas con promedio < 7 de todos los historiales de ese estudiante-grado
        $notasReprobadas = $grupo->flatMap(function ($h) {
            return Nota::where('id_historial', $h->id_historial)
                // ->where('promedio', '<', 7)
                ->with('periodo')
                ->get();
        });

        return [
            'id_estudiante' => $estudiante->id_estudiante,
            'nombre' => $persona->nombre,
            'apellido' => $persona->apellido,
            'notas' => $notasReprobadas->map(function ($nota) {
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
            })->values()
        ];
    })->values();

    return response()->json([
        'total_repetidores' => $estudiantes->count(),
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
