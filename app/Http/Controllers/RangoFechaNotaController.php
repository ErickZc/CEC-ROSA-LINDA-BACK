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
        // Validar los datos recibidos
        $validated = $request->validate([
            'id_periodo' => 'required',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
        ]);

        // Ajustar las fechas para incluir hora
        $validated['fecha_inicio'] = Carbon::parse($validated['fecha_inicio'])->startOfDay(); // 00:00:00
        $validated['fecha_fin'] = Carbon::parse($validated['fecha_fin'])->endOfDay();   

        if ($id !== null) {
            // Buscar el rango por ID
            $rango = RangoFechaNota::find($id);
        } else {
            // No se pasó ID, buscar un rango que coincida exactamente con id_periodo y fechas (o crear uno nuevo)
            $rango = RangoFechaNota::where('id_periodo', $validated['id_periodo'])
                ->where('fecha_inicio', $validated['fecha_inicio'])
                ->where('fecha_fin', $validated['fecha_fin'])
                ->first();
        }

        if ($rango) {
            // Si existe, actualizarlo
            $rango->update($validated);
            $mensaje = 'Rango actualizado correctamente';
        } else {
            // Si no existe, crear uno nuevo
            $rango = RangoFechaNota::create($validated);
            $mensaje = 'Rango creado correctamente';
        }

        return response()->json([
            'message' => $mensaje,
            'request' => $request->all()
        ], 200);
    }



    public function destroy($id)
    {
        $rango = RangoFechaNota::findOrFail($id);
        $rango->delete();
        return response()->json(['message' => 'Rango eliminado'], 200);
    }
}

