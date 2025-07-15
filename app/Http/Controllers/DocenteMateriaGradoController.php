<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\HistorialEstudiante;
use App\Models\Docente;
use App\Models\DocenteMateriaGrado;

use Illuminate\Http\Request;

class DocenteMateriaGradoController extends Controller
{
    public function busquedaDocente(Request $request)
    {
        $nombreCompleto = $request->buscador;

        $docentes = Docente::with('persona')
            ->where('estado', 'ACTIVO')
            ->whereHas('persona', function ($query) use ($nombreCompleto) {
                $query->whereRaw("TRIM(LOWER(CONCAT(nombre, ' ', apellido))) = ?", [trim(strtolower($nombreCompleto))]);
            })
            ->get();

        return response()->json($docentes);
    }

    public function AsignarMateriaDocenteCiclo1(Request $request)
    {
        $id_docente = $request->id_docente;
        $id_grado = $request->id_grado;
        $materiasSeleccionadas = $request->materias;

        // Validación básica
        if (empty($materiasSeleccionadas) || !is_array($materiasSeleccionadas)) {
            return response()->json([
                'message' => 'Debe seleccionar al menos una materia.'
            ], 400);
        }

        // Verificar si el mismo docente ya tiene alguna de las materias asignadas este año en ese grado
        $existeAsignacion = DB::table('Docente_Materia_Grado as dmg')
            ->join('Docente as d', 'dmg.id_docente', '=', 'd.id_docente')
            ->where('d.estado', 'ACTIVO')
            ->where('dmg.estado', 'ACTIVO')
            ->where('dmg.id_docente', $id_docente)
            ->where('dmg.id_grado', $id_grado)
            ->whereYear('dmg.fecha_asignacion', now()->year)
            ->whereIn('dmg.id_materia', $materiasSeleccionadas)
            ->exists();

        if ($existeAsignacion) {
            return response()->json([
                'message' => 'El docente ya tiene asignada una o más de las materias seleccionadas para este grado en el año actual.'
            ], 409);
        }

        // Verificar si otro docente ya tiene alguna de las materias asignadas este año en ese grado
        $materiaOcupada = DB::table('Docente_Materia_Grado as dmg')
            ->join('Docente as d', 'dmg.id_docente', '=', 'd.id_docente')
            ->where('d.estado', 'ACTIVO')
            ->where('dmg.estado', 'ACTIVO')
            ->where('dmg.id_grado', $id_grado)
            ->whereYear('dmg.fecha_asignacion', now()->year)
            ->whereIn('dmg.id_materia', $materiasSeleccionadas)
            ->where('dmg.id_docente', '!=', $id_docente)
            ->exists();

        if ($materiaOcupada) {
            return response()->json([
                'message' => 'Una o más materias ya están siendo impartidas por otro docente en este grado.'
            ], 409);
        }

        // Insertar las materias seleccionadas
        $fechaHoy = now();
        $datosInsertar = [];

        foreach ($materiasSeleccionadas as $id_materia) {
            $datosInsertar[] = [
                'id_docente' => $id_docente,
                'id_materia' => $id_materia,
                'id_grado' => $id_grado,
                'fecha_asignacion' => $fechaHoy,
                'estado' => 'ACTIVO'
            ];
        }

        DB::table('Docente_Materia_Grado')->insert($datosInsertar);

        return response()->json([
            'message' => 'Materias asignadas correctamente al docente.'
        ], 200);
    }

    public function AsignarMateriaDocenteCiclo2(Request $request)
    {
        $id_docente = $request->id_docente;
        $id_materia = $request->id_materia;
        $grados = $request->grados; // array de ID de grados

        if (empty($grados) || !is_array($grados)) {
            return response()->json([
                'message' => 'Debe seleccionar al menos un grado.'
            ], 422);
        }

        // Verificar si este docente ya tiene asignada la materia en alguno de los grados seleccionados este año
        $asignacionExistente = DB::table('Docente_Materia_Grado')
            ->where('id_docente', $id_docente)
            ->where('id_materia', $id_materia)
            ->whereIn('id_grado', $grados)
            ->whereYear('fecha_asignacion', now()->year)
            ->where('estado', 'ACTIVO')
            ->exists();

        if ($asignacionExistente) {
            return response()->json([
                'message' => 'El docente ya tiene asignada esta materia en al menos uno de los grados seleccionados.'
            ], 409);
        }

        // Verificar si otro docente ya imparte esa materia en alguno de los grados seleccionados
        $materiaOcupada = DB::table('Docente_Materia_Grado as dmg')
            ->join('Docente as d', 'd.id_docente', '=', 'dmg.id_docente')
            ->where('d.estado', 'ACTIVO')
            ->where('dmg.estado', 'ACTIVO')
            ->where('dmg.id_materia', $id_materia)
            ->whereYear('dmg.fecha_asignacion', now()->year)
            ->whereIn('dmg.id_grado', $grados)
            ->where('dmg.id_docente', '!=', $id_docente)
            //->select('dmg.id_grado', 'd.nombres', 'd.apellidos')
            ->get();

        if ($materiaOcupada->isNotEmpty()) {
            // Opcional: devolver los grados o nombres de docentes que causan conflicto
            return response()->json([
                'message' => 'Esta materia ya está siendo impartida por otro docente en uno o más de los grados seleccionados.',
                'conflictos' => $materiaOcupada // Puedes usarlo en el frontend si lo deseas
            ], 409);
        }

        // Insertar nuevas asignaciones
        $fechaHoy = now();
        $datosInsertar = [];

        foreach ($grados as $id_grado) {
            $datosInsertar[] = [
                'id_docente' => $id_docente,
                'id_materia' => $id_materia,
                'id_grado' => $id_grado,
                'fecha_asignacion' => $fechaHoy,
                'estado' => 'ACTIVO'
            ];
        }

        DB::table('Docente_Materia_Grado')->insert($datosInsertar);

        return response()->json([
            'message' => 'Materias asignadas correctamente al docente.'
        ], 200);
    }

    public function AsignarMateriaDocenteCiclo3(Request $request)
    {
        $id_docente = $request->id_docente;
        $id_materia = $request->id_materia;
        $grados = $request->grados; // array de ID de grados

        if (empty($grados) || !is_array($grados)) {
            return response()->json([
                'message' => 'Debe seleccionar al menos un grado.'
            ], 422);
        }

        // Verificar si este docente ya tiene asignada la materia en alguno de los grados seleccionados este año
        $asignacionExistente = DB::table('Docente_Materia_Grado')
            ->where('id_docente', $id_docente)
            ->where('id_materia', $id_materia)
            ->whereIn('id_grado', $grados)
            ->whereYear('fecha_asignacion', now()->year)
            ->where('estado', 'ACTIVO')
            ->exists();

        if ($asignacionExistente) {
            return response()->json([
                'message' => 'El docente ya tiene asignada esta materia en al menos uno de los grados seleccionados.'
            ], 409);
        }

        // Verificar si otro docente ya imparte esa materia en alguno de los grados seleccionados
        $materiaOcupada = DB::table('Docente_Materia_Grado as dmg')
            ->join('Docente as d', 'd.id_docente', '=', 'dmg.id_docente')
            ->where('d.estado', 'ACTIVO')
            ->where('dmg.estado', 'ACTIVO')
            ->where('dmg.id_materia', $id_materia)
            ->whereYear('dmg.fecha_asignacion', now()->year)
            ->whereIn('dmg.id_grado', $grados)
            ->where('dmg.id_docente', '!=', $id_docente)
            //->select('dmg.id_grado', 'd.nombres', 'd.apellidos')
            ->get();

        if ($materiaOcupada->isNotEmpty()) {
            // Opcional: devolver los grados o nombres de docentes que causan conflicto
            return response()->json([
                'message' => 'Esta materia ya está siendo impartida por otro docente en uno o más de los grados seleccionados.',
                'conflictos' => $materiaOcupada // Puedes usarlo en el frontend si lo deseas
            ], 409);
        }

        // Insertar nuevas asignaciones
        $fechaHoy = now();
        $datosInsertar = [];

        foreach ($grados as $id_grado) {
            $datosInsertar[] = [
                'id_docente' => $id_docente,
                'id_materia' => $id_materia,
                'id_grado' => $id_grado,
                'fecha_asignacion' => $fechaHoy,
                'estado' => 'ACTIVO'
            ];
        }

        DB::table('Docente_Materia_Grado')->insert($datosInsertar);

        return response()->json([
            'message' => 'Materias asignadas correctamente al docente.'
        ], 200);
    }

    public function AsignarMateriaDocenteCiclo4(Request $request)
    {
        $id_docente = $request->id_docente;
        $id_materia = $request->id_materia;
        $grados = $request->grados; // array de ID de grados

        if (empty($grados) || !is_array($grados)) {
            return response()->json([
                'message' => 'Debe seleccionar al menos un grado.'
            ], 422);
        }

        // Verificar si este docente ya tiene asignada la materia en alguno de los grados seleccionados este año
        $asignacionExistente = DB::table('Docente_Materia_Grado')
            ->where('id_docente', $id_docente)
            ->where('id_materia', $id_materia)
            ->whereIn('id_grado', $grados)
            ->whereYear('fecha_asignacion', now()->year)
            ->where('estado', 'ACTIVO')
            ->exists();

        if ($asignacionExistente) {
            return response()->json([
                'message' => 'El docente ya tiene asignada esta materia en al menos uno de los grados seleccionados.'
            ], 409);
        }

        // Verificar si otro docente ya imparte esa materia en alguno de los grados seleccionados
        $materiaOcupada = DB::table('Docente_Materia_Grado as dmg')
            ->join('Docente as d', 'd.id_docente', '=', 'dmg.id_docente')
            ->where('d.estado', 'ACTIVO')
            ->where('dmg.estado', 'ACTIVO')
            ->where('dmg.id_materia', $id_materia)
            ->whereYear('dmg.fecha_asignacion', now()->year)
            ->whereIn('dmg.id_grado', $grados)
            ->where('dmg.id_docente', '!=', $id_docente)
            //->select('dmg.id_grado', 'd.nombres', 'd.apellidos')
            ->get();

        if ($materiaOcupada->isNotEmpty()) {
            // Opcional: devolver los grados o nombres de docentes que causan conflicto
            return response()->json([
                'message' => 'Esta materia ya está siendo impartida por otro docente en uno o más de los grados seleccionados.',
                'conflictos' => $materiaOcupada // Puedes usarlo en el frontend si lo deseas
            ], 409);
        }

        // Insertar nuevas asignaciones
        $fechaHoy = now();
        $datosInsertar = [];

        foreach ($grados as $id_grado) {
            $datosInsertar[] = [
                'id_docente' => $id_docente,
                'id_materia' => $id_materia,
                'id_grado' => $id_grado,
                'fecha_asignacion' => $fechaHoy,
                'estado' => 'ACTIVO'
            ];
        }

        DB::table('Docente_Materia_Grado')->insert($datosInsertar);

        return response()->json([
            'message' => 'Materias asignadas correctamente al docente.'
        ], 200);
    }

    public static function obtenerMateriasConDocentesPorGrado()
    {
        return DB::table(DB::raw('Materia m'))
            ->join('Ciclo as c', 'm.id_ciclo', '=', 'c.id_ciclo')
            ->crossJoin('Grado as g')
            ->join('Seccion as s', 'g.id_seccion', '=', 's.id_seccion')
            ->leftJoin('Docente_Materia_Grado as dmg', function ($join) {
                $join->on('dmg.id_materia', '=', 'm.id_materia')
                    ->on('dmg.id_grado', '=', 'g.id_grado')
                    ->where('dmg.estado', '=', 'ACTIVO')
                    ->whereYear('dmg.fecha_asignacion', '=', now()->year);
            })
            ->leftJoin('Docente as d', 'd.id_docente', '=', 'dmg.id_docente')
            ->leftJoin('Persona as p', 'p.id_persona', '=', 'd.id_persona')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereIn('g.grado', ['Primero','Segundo', 'Tercero'])->where('m.id_ciclo', 1);
                })->orWhere(function ($q) {
                    $q->whereIn('g.grado', ['Cuarto', 'Quinto', 'Sexto'])->where('m.id_ciclo', 2);
                })->orWhere(function ($q) {
                    $q->whereIn('g.grado', ['Séptimo', 'Octavo', 'Noveno'])->where('m.id_ciclo', 3);
                })->orWhere(function ($q) {
                    $q->whereIn('g.grado', ['1er Bachillerato', '2do Bachillerato'])->where('m.id_ciclo', 4);
                });
            })
            ->selectRaw("
                g.id_grado,
                CONCAT(g.grado, ' ', s.seccion) AS grado_seccion,
                g.turno,
                m.id_materia,
                m.nombre_materia,
                d.id_docente,
                COALESCE(CONCAT(p.nombre, ' ', p.apellido), 'Docente no asignado') AS docente_asignado,
                COALESCE(DATE_FORMAT(dmg.fecha_asignacion, '%d-%m-%Y'), 'No disponible') AS fecha_asignacion
            ")
            ->orderByRaw("
                CASE 
                    WHEN g.grado = 'Primero' THEN 1
                    WHEN g.grado = 'Segundo' THEN 2
                    WHEN g.grado = 'Tercero' THEN 3
                    WHEN g.grado = 'Cuarto' THEN 4
                    WHEN g.grado = 'Quinto' THEN 5
                    WHEN g.grado = 'Sexto' THEN 6
                    WHEN g.grado = 'Séptimo' THEN 7
                    WHEN g.grado = 'Octavo' THEN 8
                    WHEN g.grado = 'Noveno' THEN 9
                    WHEN g.grado = '1er Bachillerato' THEN 10
                    WHEN g.grado = '2do Bachillerato' THEN 11
                    ELSE 99
                END,
                s.seccion,
                m.nombre_materia
            ")
            ->get();
    }

    public function desvincularDocenteMateriaGrado(Request $request)
    {
        try {
            $id_docente = $request->id_docente;
            $id_materia = $request->id_materia;
            $id_grado   = $request->id_grado;

            // Ejecutar UPDATE usando Query Builder
            $actualizados = DB::table('Docente_Materia_Grado')
                ->where('id_docente', $id_docente)
                ->where('id_materia', $id_materia)
                ->where('id_grado', $id_grado)
                ->whereYear('fecha_asignacion', now()->year)
                ->update(['estado' => 'INACTIVO']);

            if ($actualizados === 0) {
                return response()->json([
                    'message' => 'No se encontró ninguna asignación activa para desvincular.',
                ], 404);
            }

            return response()->json([
                'message' => 'El docente fue desvinculado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al intentar desvincular al docente.'
            ], 500);
        }
    }

    public function getDMDashboardCountsByDocente(Request $request)
    {
        $docenteId = $request->input('docente');
        $anioActual = date('Y');

        $asignaciones = DocenteMateriaGrado::with(['grado.estudiante'])
            ->when($docenteId, function ($query) use ($docenteId) {
                $query->whereHas('docente.persona', function ($q) use ($docenteId) {
                    $q->where('id_persona', $docenteId);
                });
            })
            ->get();

        $materiasCount = $asignaciones->pluck('materia.id')->unique()->count();

        $grados = $asignaciones->pluck('grado')->unique('id_grado');
        $gradosCount = $asignaciones->pluck('id_grado')->unique()->count();

        $gradoIds = $grados->pluck('id_grado');
        $estudiantesCount = HistorialEstudiante::where('anio', $anioActual)->whereIn('id_grado', $gradoIds)->distinct('id_estudiante')->count();


        return response()->json([
            'materias' => $materiasCount,
            'grados' => $gradosCount,
            'estudiantes' => $estudiantesCount
        ]);
    }

    public function getMateriasByDocente(Request $request)
    {
        $docenteId = $request->input('docente');

        $asignaciones = DocenteMateriaGrado::with(['grado', 'grado.seccion', 'materia'])
            ->when($docenteId, function ($query) use ($docenteId) {
                $query->whereHas('docente.persona', function ($q) use ($docenteId) {
                    $q->where('id_persona', $docenteId);
                });
            })
            ->get();

        return response()->json($asignaciones);
    }
}
