<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inasistencia;
use App\Models\HistorialEstudiante;
use Carbon\Carbon;

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

    public function getInasistenciaReport(Request $request)
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
}
