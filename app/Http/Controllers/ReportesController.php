<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Grado;
use App\Models\Nota;
use App\Models\HistorialEstudiante;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\DocenteMateriaGrado;
use App\Models\Persona;
use App\Models\Periodo;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function generarBoletaXestudiante($id_estudiante, Request $request)
    {
        $anio = $request->input('anio', date('Y'));

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
        $asistencias = 0;

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

        $data = [
            'grado' => $grado->grado,
            'seccion' => $grado->seccion->seccion,
            'turno' => $grado->turno,
            'materia' => $materia->nombre_materia, 
            'docente' => $nombreDocente,
            'estudiantes' => $estudiantes,
            'institucion' => 'Complejo Educativo Col. Rosa Linda',
            'anio' => Carbon::now()->year,
            'periodo' => $nombrePeriodo
        ];

        $pdf = Pdf::loadView('reportes.reporteNotas', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $nombreArchivo, [
            'Content-Type' => 'application/pdf',
        ]);
    }   
}
