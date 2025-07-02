<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HabilitacionDocente;
use App\Models\RangoFechaNota;
use Carbon\Carbon;
use App\Models\Persona;
use App\Models\Periodo;
use App\Models\Docente;

class NotaAccesoController extends Controller
{
   
    
    public function index()
    {
        return HabilitacionDocente::with(['docente.persona', 'periodo'])->paginate(10);
    }





    public function storeRangoFecha(Request $request)
    {
        $request->validate([
            'id_docente' => 'required|integer|exists:Docente,id_docente',
            'id_periodo' => 'required|integer|exists:Periodo,id_periodo',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $rango = RangoFechaNota::create([
            'id_docente' => $request->id_docente,
            'id_periodo' => $request->id_periodo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => 'ACTIVO',
        ]);

        return response()->json([
            'message' => 'Rango de fechas creado correctamente.',
            'data' => $rango
        ], 201);
    }

    // Función para verificar si el docente puede ingresar notas en la fecha actual
    public function puedeIngresarNotas($idRol, $idPersona, $id_periodo)
    {
        if (!in_array($idRol, [1, 2])) {
            return response()->json(['error' => 'No autorizado. Rol no permitido.'], 200);
        }

        if ($idRol == 1) { // ADMINISTRADOR
            return response()->json([
                'rol' => 'ADMINISTRADOR',
                'puede_ingresar' => true,
                'mensaje' => 'Acceso autorizado para administrador.'
            ]);
        }

        $docente = Docente::where('id_persona', $idPersona)->first();

        if (!$docente) {
            return response()->json(['error' => 'El usuario no está registrado como docente.'], 200);
        }

        $hoy = Carbon::now('America/El_Salvador');

        // Verificar rango general activo para el periodo
        $rangoGlobal = RangoFechaNota::where('id_periodo', $id_periodo)
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->first();

        if ($rangoGlobal) {
            return response()->json([
                'rol' => 'DOCENTE',
                'puede_ingresar' => true,
                'mensaje' => 'Acceso autorizado por rango general activo.'
            ]);
        }

        // Verificar habilitación personalizada para docente y periodo
        $habilitacionEspecial = HabilitacionDocente::where('id_docente', $docente->id_docente)
            ->where('id_periodo', $id_periodo)
            ->where('estado', 'ACTIVO')
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->first();

        if ($habilitacionEspecial) {
            return response()->json([
                'rol' => 'DOCENTE',
                'puede_ingresar' => true,
                'mensaje' => 'Acceso autorizado por habilitación especial.'
            ]);
        }

        return response()->json([
            'rol' => 'DOCENTE',
            'puede_ingresar' => false,
            'error' => 'Acceso denegado. El periodo ha expirado. Contacte al administrador del sistema.'
        ], 200);
    }

    public function guardarHabilitacion(Request $request, $id = null)
    {
        $validated = $request->validate([
            'id_docente' => 'required|integer|exists:docente,id_docente',
            'id_periodo' => 'required|integer|exists:periodo,id_periodo',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo' => 'nullable|string|max:255',
            'estado' => 'nullable|in:ACTIVO,INACTIVO',
        ]);

        $data = [
            'id_docente' => $validated['id_docente'],
            'id_periodo' => $validated['id_periodo'],
            'fecha_inicio' => Carbon::parse($validated['fecha_inicio'])->startOfDay(),
            'fecha_fin' => Carbon::parse($validated['fecha_fin'])->endOfDay(),
            'motivo' => $validated['motivo'] ?? '',
            'estado' => $validated['estado'] ?? 'ACTIVO',
        ];

        if ($id) {
            // Actualizar
            $habilitacion = HabilitacionDocente::find($id);
            if (!$habilitacion) {
                return response()->json(['error' => 'Habilitación no encontrada'], 404);
            }
            $habilitacion->update($data);

            return response()->json([
                'message' => 'Habilitación actualizada correctamente',
                'data' => $habilitacion,
            ], 200);
        } else {
            // Crear
            $habilitacion = HabilitacionDocente::create($data);

            return response()->json([
                'message' => 'Habilitación creada correctamente',
                'data' => $habilitacion,
            ], 201);
        }
    }


    public function destroy($id)
    {
        $habilitacion = HabilitacionDocente::findOrFail($id);
        $habilitacion->delete();

        return response()->json(['message' => 'Habilitación eliminada correctamente']);
    }



}
