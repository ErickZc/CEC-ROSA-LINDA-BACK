<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inasistencia;
use App\Models\HistorialEstudiante;
use Carbon\Carbon;
use App\Models\DocenteMateriaGrado;

class InasistenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inasistencia::all();
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

    public function getInasistenciaReportByGrado(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $grado = $request->input('grado'); //obligatorio
        $nombre = $request->input('search', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        if (!$grado) {
            return response()->json(['error' => 'Grado es obligatorio'], 400);
        }

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado'])
            ->when($grado, function ($query) use ($grado) {
                $query->whereHas('historialestudiante.grado', function ($q) use ($grado) {
                    $q->where('id_grado', "{$grado}");
                });
            })
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->whereBetween('fecha', [$desde, $hasta])
            ->paginate(10);

        // Devolver los usuarios paginados
        return response()->json($inasistencias);
    }

    public function getInasistenciasByDocente(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $nombre = $request->input('search', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $docente = $request->input('docente');

        $grados = DocenteMateriaGrado::with([
            'docente.persona',
            'grado'
        ])
            ->when($docente, function ($query) use ($docente) {
                $query->whereHas('docente.persona', function ($q) use ($docente) {
                    $q->where('id_persona', "$docente");
                });
            })
            ->pluck('id_grado')
            ->unique()
            ->values();

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado', 'historialestudiante.grado.seccion'])
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->when(!empty($grados), function ($query) use ($grados) {
                $query->whereHas('historialestudiante.grado', function ($q) use ($grados) {
                    $q->whereIn('id_grado', $grados);
                });
            })
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        return response()->json($inasistencias);
    }

    public function getInasistenciasByResponsable(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $nombre = $request->input('search', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $responsable = $request->input('responsable');

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado', 'historialestudiante.grado.seccion'])
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->when($responsable, function ($query) use ($responsable) {
                $query->whereHas('historialestudiante.estudiante.responsableEstudiantes.responsable.persona', function ($q) use ($responsable) {
                    $q->where('id_persona', $responsable);
                });
            })
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        return response()->json($inasistencias);
    }

    public function getAllInasistencias(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $nombre = $request->input('search', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado', 'historialestudiante.grado.seccion'])
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha', 'desc')
            ->paginate(10);

        // Devolver los usuarios paginados
        return response()->json($inasistencias);
    }

    public function getAllInasistenciasExport(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $nombre = $request->input('search', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado', 'historialestudiante.grado.seccion'])
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->whereBetween('fecha', [$desde, $hasta])
            ->get();

        return response()->json($inasistencias);
    }

    public function getAllInasistenciasByDocenteExport(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $nombre = $request->input('search', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $docente = $request->input('docente');

        $grados = DocenteMateriaGrado::with([
            'docente.persona',
            'grado'
        ])
            ->when($docente, function ($query) use ($docente) {
                $query->whereHas('docente.persona', function ($q) use ($docente) {
                    $q->where('id_persona', "$docente");
                });
            })
            ->pluck('id_grado')
            ->unique()
            ->values();

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado', 'historialestudiante.grado.seccion'])
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->when(!empty($grados), function ($query) use ($grados) {
                $query->whereHas('historialestudiante.grado', function ($q) use ($grados) {
                    $q->whereIn('id_grado', $grados);
                });
            })
            ->whereBetween('fecha', [$desde, $hasta])
            ->get();

        return response()->json($inasistencias);
    }

    public function getInasistenciasByResponsableExport(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $nombre = $request->input('search', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $responsable = $request->input('responsable');

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado', 'historialestudiante.grado.seccion'])
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->when($responsable, function ($query) use ($responsable) {
                $query->whereHas('historialestudiante.estudiante.responsableEstudiantes.responsable.persona', function ($q) use ($responsable) {
                    $q->where('id_persona', $responsable);
                });
            })
            ->whereBetween('fecha', [$desde, $hasta])
            ->get();

        return response()->json($inasistencias);
    }

    public function getInasistenciaCount(Request $request)
    {
        $grado = $request->input('grado', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        if (!$grado) {
            return response()->json(['error' => 'Grado es obligatorio'], 400);
        }

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Calcular cuántos días hábiles hay (Lunes a Viernes)
        $diasHabiles = 0;
        $fechaActual = $desde->copy();

        while ($fechaActual->lte($hasta)) {
            if ($fechaActual->isWeekday()) { // Lunes a Viernes
                $diasHabiles++;
            }
            $fechaActual->addDay();
        }

        // Total de estudiantes en el grado
        $totalEstudiantes = HistorialEstudiante::where('id_grado', $grado)->count();

        // Total de inasistencias filtradas por grado y fechas
        $inasistenciasQuery = Inasistencia::whereHas('historialestudiante.grado', function ($q) use ($grado) {
            $q->where('id_grado', $grado);
        });

        $inasistenciasQuery->whereBetween('fecha', [$desde, $hasta]);
        $totalInasistencias = $inasistenciasQuery->count();

        // Asistencias posibles = estudiantes * días hábiles
        $asistenciasPosibles = $totalEstudiantes * $diasHabiles;
        $totalAsistencias = $asistenciasPosibles - $totalInasistencias;

        return response()->json([
            'asistencias' => $totalAsistencias,
            'inasistencias' => $totalInasistencias,
            'estudiantes' => $totalEstudiantes,
            'dias_habiles' => $diasHabiles,
            'rango' => [
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString()
            ]
        ]);
    }

    public function getInasistenciaByDays(Request $request)
    {
        $grado = $request->input('grado', '');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        if (!$grado) {
            return response()->json(['error' => 'Grado es obligatorio'], 400);
        }

        // Si no se envían fechas, tomar la última semana (lunes a viernes)
        if (!$desde || !$hasta) {
            $hoy = Carbon::today();
            $ultimoViernes = $hoy->copy()->previous(Carbon::FRIDAY);
            $ultimoLunes = $ultimoViernes->copy()->previous(Carbon::MONDAY);

            $desde = $ultimoLunes->startOfDay();
            $hasta = $ultimoViernes->endOfDay();
        } else {
            $desde = Carbon::parse($desde)->startOfDay();
            $hasta = Carbon::parse($hasta)->endOfDay();
        }

        // Total de estudiantes en el grado
        $totalEstudiantes = HistorialEstudiante::where('id_grado', $grado)->count();

        // Obtener todas las inasistencias dentro del rango agrupadas por día
        $inasistencias = Inasistencia::whereHas('historialestudiante.grado', function ($q) use ($grado) {
            $q->where('id_grado', $grado);
        })
            ->whereBetween('fecha', [$desde, $hasta])
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->fecha)->toDateString();
            });

        // Recorrer los días del rango y construir asistencias por fecha
        $asistenciasPorDia = [];
        $fechaActual = $desde->copy();

        while ($fechaActual->lte($hasta)) {
            if ($fechaActual->isWeekday()) {
                $fechaStr = $fechaActual->toDateString();
                $inasistenciasDelDia = isset($inasistencias[$fechaStr]) ? $inasistencias[$fechaStr]->count() : 0;
                $asistenciasRealizadas = $totalEstudiantes - $inasistenciasDelDia;

                $asistenciasPorDia[] = [
                    'fecha' => $fechaStr,
                    'asistencias' => $asistenciasRealizadas,
                    'inasistencias' => $inasistenciasDelDia
                ];
            }
            $fechaActual->addDay();
        }

        return response()->json([
            'asistencias_por_dia' => $asistenciasPorDia,
            'estudiantes' => $totalEstudiantes,
            'rango' => [
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString()
            ]
        ]);
    }

    public function getInasistenciaInfoDefault()
    {
        $hoy = Carbon::today();

        $inicioSemana = $hoy->copy()->startOfWeek(Carbon::MONDAY);

        if ($hoy->isSaturday() || $hoy->isSunday()) {
            $finSemana = $inicioSemana->copy()->addDays(4)->endOfDay();
        } else {
            $finSemana = $inicioSemana->copy()->addDays(4)->endOfDay();
        }

        // Año actual
        $inicioAnio = Carbon::now()->startOfYear();
        $finAnio = Carbon::now()->endOfYear();

        // Total inasistencias en el año
        $totalAnio = Inasistencia::whereBetween('fecha', [$inicioAnio, $finAnio])->count();

        // Inasistencias en la semana seleccionada
        $inasistenciasSemana = Inasistencia::with('historialestudiante.grado')
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->get();

        $totalSemana = $inasistenciasSemana->count();


        $inasistenciasAgrupadas = $inasistenciasSemana->groupBy(function ($item) {
            return Carbon::parse($item->fecha)->toDateString();
        });

        $detallePorDia = [];
        $fechaActual = $inicioSemana->copy();

        while ($fechaActual->lte($finSemana)) {
            if ($fechaActual->isWeekday()) {
                $fechaStr = $fechaActual->toDateString();
                $cantidad = isset($inasistenciasAgrupadas[$fechaStr])
                    ? $inasistenciasAgrupadas[$fechaStr]->count()
                    : 0;

                $detallePorDia[] = [
                    'fecha' => $fechaStr,
                    'inasistencias' => $cantidad
                ];
            }

            $fechaActual->addDay();
        }

        // Agrupación por grado 
        $ordenGrados = [
            'Primero',
            'Segundo',
            'Tercero',
            'Cuarto',
            'Quinto',
            'Sexto',
            'Séptimo',
            'Octavo',
            'Noveno',
            '1er Bachillerato',
            '2do Bachillerato'
        ];

        $detallePorGrado = $inasistenciasSemana->groupBy(function ($item) {
            return optional($item->historialestudiante->grado)->grado ?? 'Sin grado';
        })->map(function ($items, $grado) {
            return [
                'grado' => $grado,
                'inasistencias' => $items->count()
            ];
        })
            ->sortBy(function ($item) use ($ordenGrados) {
                return array_search($item['grado'], $ordenGrados) !== false
                    ? array_search($item['grado'], $ordenGrados)
                    : PHP_INT_MAX; // Los que no estén en la lista van al final
            })
            ->values();

        return response()->json([
            'total_anual' => $totalAnio,
            'total_semana' => $totalSemana,
            'detalle_por_dia' => $detallePorDia,
            'detalle_por_grado' => $detallePorGrado,
            'rango_semana' => [
                'desde' => $inicioSemana->toDateString(),
                'hasta' => $finSemana->toDateString()
            ]
        ]);
    }
}
