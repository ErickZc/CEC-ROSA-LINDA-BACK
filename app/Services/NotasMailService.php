<?php

namespace App\Services;

use App\Models\RangoFechaNota;
use App\Models\Ciclo;
use App\Http\Controllers\EstudianteController;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class NotasMailService
{
    public function enviarCorreos()
    {
        setlocale(LC_TIME, 'es_ES.UTF-8'); 
        Carbon::setLocale('es');
        $hoy = Carbon::now('America/El_Salvador');

        $rangoPeriodo = RangoFechaNota::whereDate('fecha_fin', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy->copy()->subDays(3))
            ->get();

        foreach ($rangoPeriodo as $periodo) {
            $diasTranscurridos = $periodo->fecha_fin->startOfDay()->diffInDays($hoy->copy()->startOfDay());

            if ($diasTranscurridos >= 4) {
                continue;
            }

            // Elegir el ciclo correspondiente al día de envío
            $ciclos = Ciclo::orderBy('id_ciclo')->get()->values();
            $cicloSeleccionado = $ciclos[$diasTranscurridos];
            echo $periodo;
            $request = new Request([
                'id_periodo' => $periodo->id_periodo,
                'id_ciclo' => $cicloSeleccionado->id_ciclo,
            ]);

            $controller = app(EstudianteController::class);
            $controller->enviarNotasAllGradoPeriodoCiclo($request);
        }
    }
}
