<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Nota;
use Carbon\CarbonPeriod;
use App\Models\Estudiante;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Grado;
use App\Models\HistorialEstudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\DocenteMateriaGrado;
use App\Models\Persona;
use App\Models\Periodo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function generarBoletaXestudiante($id_estudiante, $anio)
    {
         if (!$anio) {
            return abort(400, 'Debe proporcionar un anio');
        }

        $estudiante = Estudiante::with(['persona', 'historiales.grado.seccion'])->findOrFail($id_estudiante);

        $historial = $estudiante->historiales()
            ->where('anio', $anio)
            ->with('grado.seccion')
            ->first();

        if (!$historial) {
            return abort(404, 'No se encontró historial para el año indicado');
        }

        //Obtener las notas con ciclo
        $notas = $historial->notas()
            ->with('materia.ciclo')
            ->get()
            ->groupBy(fn ($item) => $item->materia->nombre_materia ?? 'Sin nombre');

        //Detectar si es Bachillerato
        $primerNota = $notas->first()?->first();
        $cicloNombre = $primerNota?->materia?->ciclo?->nombre ?? '';
        $isBachillerato = Str::contains(strtolower($cicloNombre), 'bachillerato');

        // Inasistencias
        $inasistencias = $historial->inasistencias()
            ->whereYear('fecha', $anio)
            ->get();

        $justificadas = $inasistencias->where('estado', 'JUSTIFICADA')->count();
        $noJustificadas = $inasistencias->where('estado', 'INJUSTIFICADA')->count();
        $totalInasistencias = $inasistencias->count();

        $fechaHoy = Carbon::now();

        $inicioAnio = Carbon::create($anio)->startOfYear();

        $periodo = CarbonPeriod::create($inicioAnio, $fechaHoy);

        $diasHabiles = collect($periodo)->filter(function ($date) {
            return $date->isWeekday();
        })->count();


        $asistencias = $diasHabiles - $totalInasistencias;

        $nombreArchivo = $estudiante->nie . '_boleta_notas.pdf';

        // Obtener contenido de la imagen y convertir a base64
        $path = public_path('images/logo.jpg');
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return Pdf::loadView('reportes.boleta', [
            'estudiante' => $estudiante,
            'historial' => $historial,
            'notas' => $notas,
            'anio' => $anio,
            'asistencias' => $asistencias,
            'justificadas' => $justificadas,
            'noJustificadas' => $noJustificadas,
            'isBachillerato' => $isBachillerato,
            'logoBase64' => $base64,
        ])->stream($nombreArchivo);
    }

    public function generarReporteNotasPDF($id_grado, $id_materia, $id_periodo, $turno)
    {
        $turno = strtoupper(urldecode($turno));

        $grado = Grado::where('id_grado', $id_grado)
            ->where('turno', $turno)
            ->with('seccion')
            ->first();

        if (!$grado) {
            return response()->json([
                'message' => 'Grado con el turno no tiene estudiantes asociados.'
            ], 404);
        }

        $periodo = Periodo::where('id_periodo', $id_periodo)->first();
        $nombrePeriodo = $periodo ? $periodo->periodo : 'SIN_PERIODO';

        $id_seccion = $grado->id_seccion;

        $materia = Materia::find($id_materia);
        if (!$materia) {
            $materia= '';
        }

        $docenteMateria = DocenteMateriaGrado::with('docente.persona')
            ->where('id_grado', $id_grado)
            ->where('id_materia', $id_materia)
            ->where('estado', 'ACTIVO')
            ->first();


        $nombreDocente = $docenteMateria && $docenteMateria->docente && $docenteMateria->docente->persona
        ? $docenteMateria->docente->persona->nombre . ' ' . $docenteMateria->docente->persona->apellido
        : 'Sin docente asignado';


        $historiales = HistorialEstudiante::where('id_grado', $id_grado)
            ->where('estado', 'CURSANDO')
            ->whereHas('grado', function($query) use ($id_seccion) {
                $query->where('id_seccion', $id_seccion);
            })
            ->with(['estudiante.persona'])
            ->get();

        $estudiantes = $historiales->map(function ($historial) use ($id_materia, $id_periodo) {
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
                    'id_estudiante' => $historial->estudiante->id_estudiante,
                    'nombre' => $historial->estudiante->persona->nombre,
                    'apellido' => $historial->estudiante->persona->apellido,
                ],
                'notas' => $notasFiltradas->map(function ($nota) {
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
        });

        $nombreArchivo = strtoupper(urldecode("Notas_{$grado->grado}_{$grado->seccion->seccion}_{$materia->nombre_materia}_{$nombrePeriodo}_{$grado->turno}")) . ".pdf";
        $nombreGrado = strtolower($grado->grado);
        $notaMinima = str_contains($nombreGrado, 'bachillerato')
            ? config('app.nota_minima_media')
            : config('app.nota_minima_basica');

        $data = [
            'grado' => $grado->grado,
            'seccion' => $grado->seccion->seccion,
            'turno' => $grado->turno,
            'materia' => $materia->nombre_materia, 
            'docente' => $nombreDocente,
            'estudiantes' => $estudiantes,
            'institucion' => 'Complejo Educativo Col. Rosa Linda',
            'anio' => Carbon::now()->year,
            'periodo' => $nombrePeriodo,
            'nota_minima' => $notaMinima
        ];

        $pdf = Pdf::loadView('reportes.reporteNotas', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $nombreArchivo, [
            'Content-Type' => 'application/pdf',
        ]);
    }   

    /*public function mostrarBoletaNotas($id_grado, Request $request)
    {
        $anio = $request->input('anio', date('Y'));
        $perPage = $request->input('per_page', 10); // cantidad por página
        $currentPage = $request->input('page', 1);

        if (!$id_grado) {
            return response()->json(['message' => 'Debe proporcionar un id de grado'], 400);
        }

        $historiales = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion'])
            ->where('anio', $anio)
            ->where('id_grado', $id_grado)
            ->get();

        if ($historiales->isEmpty()) {
            return response()->json(['message' => 'No se encontraron historiales para ese grado y año'], 404);
        }

        $boletas = [];

        foreach ($historiales as $historial) {
            $notas = $historial->notas()->with('materia.ciclo')->get()
                ->groupBy(fn ($item) => $item->materia->nombre_materia ?? 'Sin nombre');

            $primerNota = $notas->first()?->first();
            $cicloNombre = $primerNota?->materia?->ciclo?->nombre ?? '';
            $isBachillerato = Str::contains(strtolower($cicloNombre), 'bachillerato');

            foreach ($notas as $materia => $registros) {
                $p1 = $registros->firstWhere('id_periodo', 1);
                $p2 = $registros->firstWhere('id_periodo', 2);
                $p3 = $registros->firstWhere('id_periodo', 3);
                $p4 = $isBachillerato ? $registros->firstWhere('id_periodo', 4) : null;

                $totalPeriodos = $isBachillerato ? 4 : 3;
                $suma = ($p1?->promedio ?? 0) + ($p2?->promedio ?? 0) + ($p3?->promedio ?? 0);
                if ($isBachillerato) $suma += ($p4?->promedio ?? 0);

                $promedio = $suma / $totalPeriodos;
                $estado = $promedio >= 5 ? 'Aprobado' : 'Reprobado';

                $boletas[] = [
                    'id_estudiante' => $historial->estudiante->id_estudiante,
                    'nie' => $historial->estudiante->nie,
                    'nombre' => $historial->estudiante->persona->apellido . ', ' . $historial->estudiante->persona->nombre,
                    'materia' => $materia,
                    'periodo1' => $p1?->promedio ?? null,
                    'periodo2' => $p2?->promedio ?? null,
                    'periodo3' => $p3?->promedio ?? null,
                    'periodo4' => $isBachillerato ? ($p4?->promedio ?? null) : null,
                    'promedio' => round($promedio, 1),
                    'estado' => $estado,
                ];
            }
        }

        // Manualmente aplicar paginación
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
    }*/

    public function mostrarBoletaNotas($id_grado, Request $request)
    {
        $anio = $request->input('anio', date('Y'));
        $perPage = $request->input('per_page', 10); // cantidad por página
        $currentPage = $request->input('page', 1);

        if (!$id_grado) {
            return response()->json(['message' => 'Debe proporcionar un id de grado'], 400);
        }

        $historiales = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion'])
            ->where('anio', $anio)
            ->where('id_grado', $id_grado)
            ->get();

        if ($historiales->isEmpty()) {
            return response()->json(['message' => 'No se encontraron historiales para ese grado y año'], 404);
        }

        $boletas = [];

        foreach ($historiales as $historial) {
            $notas = $historial->notas()->with('materia.ciclo')->get()
                ->groupBy(fn ($item) => $item->materia->nombre_materia ?? 'Sin nombre');

            $primerNota = $notas->first()?->first();
            $cicloNombre = $primerNota?->materia?->ciclo?->nombre ?? '';
            $isBachillerato = Str::contains(strtolower($cicloNombre), 'bachillerato');

            foreach ($notas as $materia => $registros) {
                $p1 = $registros->firstWhere('id_periodo', 1);
                $p2 = $registros->firstWhere('id_periodo', 2);
                $p3 = $registros->firstWhere('id_periodo', 3);
                $p4 = $registros->firstWhere('id_periodo', 4);

                
                $suma = ($p1?->promedio ?? 0) + ($p2?->promedio ?? 0) + ($p3?->promedio ?? 0) + ($p4?->promedio ?? 0);

                $promedio = $suma / 4;
                $minimo = $isBachillerato ? 6 : 5;
                $estado = $promedio >= $minimo ? 'Aprobado' : 'Reprobado';

                $boletas[] = [
                    'id_estudiante' => $historial->estudiante->id_estudiante,
                    'nie' => $historial->estudiante->nie,
                    'nombre' => $historial->estudiante->persona->apellido . ', ' . $historial->estudiante->persona->nombre,
                    'materia' => $materia,
                    'periodo1' => $p1?->promedio ?? null,
                    'periodo2' => $p2?->promedio ?? null,
                    'periodo3' => $p3?->promedio ?? null,
                    'periodo4' => $p4?->promedio ?? null,
                    'promedio' => round($promedio, 1),
                    'estado' => $estado,
                ];
            }
        }

        // Manualmente aplicar paginación
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


    /*public function generarBoletasXGrado($id_grado, $anio)
    {
        if (!$id_grado || !$anio) {
            return abort(400, 'Debe proporcionar un id de grado');
        }

        $historiales = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion', 'notas.materia.ciclo'])
            ->where('anio', $anio)
            ->where('id_grado', $id_grado)
            ->get();

        if ($historiales->isEmpty()) {
            return abort(404, 'No se encontraron historiales para ese grado y año');
        }

        // Obtener todas las materias que deberían tener nota en este grado y año
        $materiasEsperadas = Nota::whereIn('id_historial', function ($query) use ($id_grado, $anio) {
            $query->select('id_historial')
                ->from('Historial_Estudiante')
                ->where('id_grado', $id_grado)
                ->where('anio', $anio);
        })->pluck('id_materia')->unique();

        $boletas = $historiales->map(function ($historial) use ($anio, $materiasEsperadas) {
            $notas = $historial->notas->groupBy(fn($item) => $item->materia->nombre_materia ?? 'Sin nombre');

            $primerNota = $notas->first()?->first();
            $cicloNombre = $primerNota?->materia?->ciclo?->nombre ?? '';
            $isBachillerato = Str::contains(strtolower($cicloNombre), 'bachillerato');

            $inasistencias = $historial->inasistencias()->whereYear('fecha', $anio)->get();
            $justificadas = $inasistencias->where('estado', 'JUSTIFICADA')->count();
            $noJustificadas = $inasistencias->where('estado', 'INJUSTIFICADA')->count();
            $totalInasistencias = $inasistencias->count();

            $fechaHoy = Carbon::now();
            $inicioAnio = Carbon::create($anio)->startOfYear();
            $periodo = CarbonPeriod::create($inicioAnio, $fechaHoy);
            $diasHabiles = collect($periodo)->filter(fn($date) => $date->isWeekday())->count();
            $asistencias = $diasHabiles - $totalInasistencias;

            // === Evaluación del estado del estudiante ===
            $estadoFinal = $historial->estado ?? 'CURSANDO';

            if ($estadoFinal !== 'RETIRADO') {
                $todasAprobadas = true;
                $todasMateriasCompletas = true;

                foreach ($materiasEsperadas as $id_materia) {
                    $registros = $historial->notas->where('id_materia', $id_materia);

                    $p1 = $registros->firstWhere('id_periodo', 1);
                    $p2 = $registros->firstWhere('id_periodo', 2);
                    $p3 = $registros->firstWhere('id_periodo', 3);
                    $p4 = $isBachillerato ? $registros->firstWhere('id_periodo', 4) : null;

                    $notasCompletas = $p1 && $p2 && $p3 && (!$isBachillerato || $p4);

                    if (!$notasCompletas) {
                        $todasMateriasCompletas = false;
                        break;
                    }

                    $suma = ($p1->promedio) + ($p2->promedio) + ($p3->promedio);
                    if ($isBachillerato) $suma += $p4->promedio;

                    $promedio = $suma / ($isBachillerato ? 4 : 3);

                    if ($promedio < 5) {
                        $todasAprobadas = false;
                    }
                }

                if ($todasMateriasCompletas) {
                    $estadoCalculado = $todasAprobadas ? 'APROBADO' : 'REPROBADO';

                    if ($historial->estado !== $estadoCalculado) {
                        $historial->estado = $estadoCalculado;
                        $historial->save();
                    }

                    $estadoFinal = $estadoCalculado;
                } else {
                    // Si faltan notas, dejar en CURSANDO
                    if ($historial->estado !== 'CURSANDO') {
                        $historial->estado = 'CURSANDO';
                        $historial->save();
                    }

                    $estadoFinal = 'CURSANDO';
                }
            }

            return [
                'estudiante' => $historial->estudiante,
                'historial' => $historial,
                'notas' => $notas,
                'anio' => $anio,
                'asistencias' => $asistencias,
                'justificadas' => $justificadas,
                'noJustificadas' => $noJustificadas,
                'isBachillerato' => $isBachillerato,
                'estado_final' => $estadoFinal,
            ];
        });

        $path = public_path('images/logo.jpg');
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return Pdf::loadView('reportes.boleta_grado', [
            'boletas' => $boletas,
            'logoBase64' => $logoBase64,
            'anio' => $anio,
            'grado' => $historiales->first()->grado,
        ])->stream('boletas_grado_' . $id_grado . '_' . $anio . '.pdf');
    }*/

    public function generarBoletasXGrado($id_grado, $anio)
    {
        if (!$id_grado || !$anio) {
            return abort(400, 'Debe proporcionar un id de grado');
        }

        $historiales = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion', 'notas.materia.ciclo'])
            ->where('anio', $anio)
            ->where('id_grado', $id_grado)
            ->get();

        if ($historiales->isEmpty()) {
            return abort(404, 'No se encontraron historiales para ese grado y año');
        }

        // Obtener todas las materias que deberían tener nota en este grado y año
        $materiasEsperadas = Nota::whereIn('id_historial', function ($query) use ($id_grado, $anio) {
            $query->select('id_historial')
                ->from('Historial_Estudiante')
                ->where('id_grado', $id_grado)
                ->where('anio', $anio);
        })->pluck('id_materia')->unique();

        $boletas = $historiales->map(function ($historial) use ($anio, $materiasEsperadas) {
            $notas = $historial->notas->groupBy(fn($item) => $item->materia->nombre_materia ?? 'Sin nombre');

            $primerNota = $notas->first()?->first();
            $cicloNombre = $primerNota?->materia?->ciclo?->nombre ?? '';
            $isBachillerato = Str::contains(strtolower($cicloNombre), 'bachillerato');

            $inasistencias = $historial->inasistencias()->whereYear('fecha', $anio)->get();
            $justificadas = $inasistencias->where('estado', 'JUSTIFICADA')->count();
            $noJustificadas = $inasistencias->where('estado', 'INJUSTIFICADA')->count();
            $totalInasistencias = $inasistencias->count();

            $fechaHoy = Carbon::now();
            $inicioAnio = Carbon::create($anio)->startOfYear();
            $periodo = CarbonPeriod::create($inicioAnio, $fechaHoy);
            $diasHabiles = collect($periodo)->filter(fn($date) => $date->isWeekday())->count();
            $asistencias = $diasHabiles - $totalInasistencias;

            // === Evaluación del estado del estudiante ===
            $estadoFinal = $historial->estado ?? 'CURSANDO';

            if ($estadoFinal !== 'RETIRADO') {
                $todasAprobadas = true;
                $todasMateriasCompletas = true;

                foreach ($materiasEsperadas as $id_materia) {
                    $registros = $historial->notas->where('id_materia', $id_materia);

                    $p1 = $registros->firstWhere('id_periodo', 1);
                    $p2 = $registros->firstWhere('id_periodo', 2);
                    $p3 = $registros->firstWhere('id_periodo', 3);
                    $p4 = $registros->firstWhere('id_periodo', 4);

                    $notasCompletas = $p1 && $p2 && $p3 && $p4;

                    if (!$notasCompletas) {
                        $todasMateriasCompletas = false;
                        break;
                    }

                    $suma = ($p1->promedio) + ($p2->promedio) + ($p3->promedio) + ($p4->promedio);

                    $promedio = $suma / 4;

                    $notaMinima = $isBachillerato ? 6 : 5;

                    if ($promedio < $notaMinima) {
                        $todasAprobadas = false;
                    }
                }

                if ($todasMateriasCompletas) {
                    $estadoCalculado = $todasAprobadas ? 'APROBADO' : 'REPROBADO';

                    if ($historial->estado !== $estadoCalculado) {
                        $historial->estado = $estadoCalculado;
                        $historial->save();
                    }

                    $estadoFinal = $estadoCalculado;
                } else {
                    // Si faltan notas, dejar en CURSANDO
                    if ($historial->estado !== 'CURSANDO') {
                        $historial->estado = 'CURSANDO';
                        $historial->save();
                    }

                    $estadoFinal = 'CURSANDO';
                }
            }

            return [
                'estudiante' => $historial->estudiante,
                'historial' => $historial,
                'notas' => $notas,
                'anio' => $anio,
                'asistencias' => $asistencias,
                'justificadas' => $justificadas,
                'noJustificadas' => $noJustificadas,
                'isBachillerato' => $isBachillerato,
                'estado_final' => $estadoFinal,
            ];
        });

        $path = public_path('images/logo.jpg');
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return Pdf::loadView('reportes.boleta_grado', [
            'boletas' => $boletas,
            'logoBase64' => $logoBase64,
            'anio' => $anio,
            'grado' => $historiales->first()->grado,
        ])->stream('boletas_grado_' . $id_grado . '_' . $anio . '.pdf');
    }


    public function getEstudiantesPorGradoSeccion(Request $request, $id_grado, $seccion)
    {
        $perPage = $request->input('per_page', 10);

        $estudiantes = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion'])
            ->where('id_grado', $id_grado)
            ->where('estado', 'CURSANDO')
            ->whereHas('grado.seccion', function ($query) use ($seccion) {
                $query->where('seccion', $seccion);
            })
            ->paginate($perPage);

        $estudiantes->getCollection()->transform(function ($historial) {
            return [
                'id_estudiante' => $historial->estudiante->id_estudiante ?? null,
                'nie' => $historial->estudiante->nie ?? null,
                'nombre' => $historial->estudiante->persona->nombre ?? null,
                'apellido' => $historial->estudiante->persona->apellido ?? null,
                'estado' => $historial->estado,
            ];
        });

        return response()->json([
            'message' => 'Consulta realizada correctamente.',
            'estudiantes' => $estudiantes
        ]);
    }



    public function generarListadoEstudiantesPorGradoSeccion($id_grado, $seccion)
    {
        if (!$id_grado || !$seccion) {
            return abort(400, 'Debe proporcionar un grado y una sección.');
        }

        $estudiantes = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion'])
            ->where('id_grado', $id_grado)
            ->where('estado', 'CURSANDO')
            ->whereHas('grado.seccion', function($query) use ($seccion) {
                $query->where('seccion', $seccion);
            })
            ->get();

        if ($estudiantes->isEmpty()) {
            return abort(404, 'No se encontraron estudiantes para ese grado y sección.');
        }

        $listado = $estudiantes->map(function ($historial) {
            return [
                'nie' => $historial->estudiante->nie,
                'nombre' => $historial->estudiante->persona->nombre,
                'apellido' => $historial->estudiante->persona->apellido,
                'estado' => $historial->estado,
            ];
        });

        // Cargar logo (opcional)
        $path = public_path('images/logo.jpg');
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        // Generar PDF con vista
        return Pdf::loadView('reportes.listado_estudiantes', [
            'estudiantes' => $listado,
            'grado' => $estudiantes->first()->grado,
            'seccion' => $seccion,
            'logoBase64' => $logoBase64,
            'fecha' => Carbon::now()->format('d/m/Y'),
        ])->stream('listado_estudiantes_grado_' . $id_grado . '_seccion_' . $seccion . '.pdf');
    }




}
