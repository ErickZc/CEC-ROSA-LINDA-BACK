<?php

namespace App\Services;

use App\Models\Inasistencia;
use Illuminate\Support\Carbon;
use App\Mail\NotificacionInasistencias;
use Illuminate\Support\Facades\Mail;

class InasistenciaMailService
{
    public function enviarCorreos()
    {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        Carbon::setLocale('es');
        $hoy = Carbon::now('America/El_Salvador');

        $inasistencias = Inasistencia::with([
            'historialestudiante.estudiante.responsableEstudiantes.responsable.persona.usuario'
        ])
            ->whereDate('fecha', $hoy)
            ->get();

        $avisos = [];

        foreach ($inasistencias as $inasistencia) {
            $estudiante = $inasistencia->historialestudiante->estudiante;

            foreach ($estudiante->responsableEstudiantes as $relacion) {
                $responsable = $relacion->responsable;
                $correo = $responsable->persona->usuario->correo ?? null;

                if ($correo) {
                    $avisos[] = [
                        'correo' => $correo,
                        'nombre_responsable' => $responsable->persona->nombre,
                        'nombre_estudiante' => $estudiante->persona->nombre,
                        'fecha_inasistencia' => $inasistencia->fecha,
                    ];
                }
            }
        }

        foreach ($avisos as $index => $aviso) {
            Mail::to($aviso['correo'])->later(
                now()->addSeconds($index * 10),
                new NotificacionInasistencias(
                    $aviso['nombre_responsable'],
                    $aviso['nombre_estudiante'],
                    $aviso['fecha_inasistencia']
                )
            );
        }
    }
}
