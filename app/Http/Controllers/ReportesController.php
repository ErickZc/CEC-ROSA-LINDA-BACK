<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Estudiante;
use Illuminate\Support\Str;

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
}
