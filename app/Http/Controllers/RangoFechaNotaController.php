<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RangoFechaNota;
use Carbon\Carbon;

class RangoFechaNotaController extends Controller
{
    public function index()
    {
        return RangoFechaNota::with('periodo')->paginate(10);
    }

    public function update(Request $request, $id = null)
    {
        // Validar los datos
        $validated = $request->validate([
            'id_periodo' => 'required',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        // Ajustar fechas al inicio y fin del día
        $validated['fecha_inicio'] = Carbon::parse($validated['fecha_inicio'])->startOfDay();
        $validated['fecha_fin'] = Carbon::parse($validated['fecha_fin'])->endOfDay();

        // Validación 1: Verifica si ya existe otro registro con el mismo id_periodo
        $existeMismoPeriodo = RangoFechaNota::where('id_periodo', $validated['id_periodo'])
            ->when($id, fn($q) => $q->where('id_rango', '!=', $id))
            ->exists();

        if ($existeMismoPeriodo) {
            return response()->json([
                'error' => true,
                'message' => 'Ya existe un registro con este periodo. Este periodo ya fue asignado a otro rango.'
            ], 200);
        }

        // Validación 2: Verifica si ya existe otro registro con las mismas fechas
        $existeMismoRango = RangoFechaNota::where('fecha_inicio', $validated['fecha_inicio'])
            ->where('fecha_fin', $validated['fecha_fin'])
            ->when($id, fn($q) => $q->where('id_rango', '!=', $id))
            ->exists();

        if ($existeMismoRango) {
            return response()->json([
                'error' => true,
                'message' => 'Ya existe un registro con estas fechas.'
            ], 200);
        }

        // Si pasa validación, buscar o crear el registro
        if ($id !== null) {
            $rango = RangoFechaNota::find($id);
        } else {
            $rango = RangoFechaNota::where('id_periodo', $validated['id_periodo'])
                ->where('fecha_inicio', $validated['fecha_inicio'])
                ->where('fecha_fin', $validated['fecha_fin'])
                ->first();
        }

        if ($rango) {
            $rango->update($validated);
            $mensaje = 'Rango actualizado correctamente';
        } else {
            $rango = RangoFechaNota::create($validated);
            $mensaje = 'Rango creado correctamente';
        }

        return response()->json([
            'error' => false,
            'message' => $mensaje,
            'request' => $validated
        ], 200);
    }



    public function destroy($id)
    {
        $rango = RangoFechaNota::findOrFail($id);
        $rango->delete();
        return response()->json(['message' => 'Rango eliminado'], 200);
    }
}

