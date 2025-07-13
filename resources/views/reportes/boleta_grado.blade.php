<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        .encabezado { text-align: center; font-weight: bold; }
        .firmas {margin-top: 60px; display: flex; justify-content: space-between; }
        .firma { width: 45%; text-align: center; }
        /* Salto de página para PDF */
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($boletas as $index => $boleta)
        <div class="encabezado">
            <img src="{{ $logoBase64 }}" style="height: 60px; float: left;">
            <h4>MINISTERIO DE EDUCACIÓN, CIENCIA Y TECNOLOGÍA</h4>
            <p>GERENCIA DE ACREDITACIÓN INSTITUCIONAL<br>
            DEPARTAMENTO DE REGISTRO ACADÉMICO DE C.E.<br>
            <strong>BOLETA DE CALIFICACIONES</strong></p>
        </div>

        <table>
            <tr>
                <td><strong>Sede Educativa</strong></td>
                <td colspan="3">11518 - COMPLEJO EDUCATIVO "COLONIA ROSA LINDA"</td>
            </tr>

            @php
                $gradoTexto = strtolower($boleta['historial']->grado->grado);
                $esBachillerato = str_contains($gradoTexto, 'año') || str_contains($gradoTexto, 'bachillerato');
            @endphp
            <tr>
                <td><strong>Servicio Educativo</strong></td>
                <td colspan="3">
                    @if($esBachillerato)
                        Educación Media - Único - Bachillerato General
                    @else
                        Educación Básica - Único - {{ ucfirst($boleta['historial']->grado->grado) }} Grado
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Plan de Estudio</strong></td>
                <td colspan="3">PL{{ $boleta['anio'] ?? '____' }} - GENERAL - REGULAR</td>
            </tr>
            <tr>
                <td><strong>Grado</strong></td>
                <td>{{ $boleta['historial']->grado->grado }}</td>
                <td><strong>Sección</strong></td>
                <td>{{ $boleta['historial']->grado->seccion->seccion }} - {{ ucfirst($boleta['historial']->grado->turno) }}</td>
            </tr>
            <tr>
                <td><strong>Estudiante</strong></td>
                <td colspan="3">{{ $boleta['estudiante']->nie }} - {{ $boleta['estudiante']->persona->apellido }}, {{ $boleta['estudiante']->persona->nombre }}</td>
            </tr>
        </table>

        <p><strong>Cuadro de asistencias al {{ now()->format('d-m-Y') }}</strong></p>
        <table>
            <tr>
                <th>Asistencias</th>
                <th>Inasistencias Justificadas</th>
                <th>Inasistencias sin Justificar</th>
            </tr>
            <tr>
                <td>{{ $boleta['asistencias'] }}</td>
                <td>{{ $boleta['justificadas'] }}</td>
                <td>{{ $boleta['noJustificadas'] }}</td>
            </tr>
        </table>

        <small>NI = Nota institucional, PP = Primera prueba recuperación, PPS = Segunda recuperación, SP = Segunda prueba, SPS = Segunda prueba segunda recuperación, NF = Nota final</small>

        <table>
            <thead>
                <tr>
                    <th>Componente plan estudio</th>
                    @for($i = 1; $i <= ($boleta['isBachillerato'] ? 4 : 3); $i++)
                        <th>Periodo {{ $i }}</th>
                    @endfor
                    <th>Nota Final</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($boleta['notas'] as $materiaNombre => $registros)
                    @php 
                        $p1 = $registros->firstWhere('id_periodo', 1);
                        $p2 = $registros->firstWhere('id_periodo', 2);
                        $p3 = $registros->firstWhere('id_periodo', 3);
                        $p4 = $boleta['isBachillerato'] ? $registros->firstWhere('id_periodo', 4) : null;

                        $totalPeriodos = $boleta['isBachillerato'] ? 4 : 3;
                        $sumaNotas = 
                            ($p1?->promedio ?? 0) + 
                            ($p2?->promedio ?? 0) + 
                            ($p3?->promedio ?? 0) + 
                            ($boleta['isBachillerato'] ? ($p4?->promedio ?? 0) : 0);
                            
                        $promedio = $sumaNotas / $totalPeriodos;
                        $minimoAprobacion = $boleta['isBachillerato'] ? 6 : 5;
                        $estado = $promedio >= $minimoAprobacion ? 'Aprobado' : 'Reprobado';
                    @endphp
                    <tr>
                        <td>{{ $materiaNombre }}</td>
                        <td>{{ number_format($p1?->promedio ?? 0, 1) }}</td>
                        <td>{{ number_format($p2?->promedio ?? 0, 1) }}</td>
                        <td>{{ number_format($p3?->promedio ?? 0, 1) }}</td>
                        @if($boleta['isBachillerato'])
                            <td>{{ number_format($p4?->promedio ?? 0, 2) }}</td>
                        @endif
                        <td>{{ number_format($promedio, 1) }}</td>
                        <td>{{ $estado }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $boleta['isBachillerato'] ? 6 : 5 }}" style="text-align: center;">No hay notas registradas</td>
                        <td colspan="1">-</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="firmas">
            <table style="width: 100%; margin-top: 60px; border: none;">
                <tr>
                    <td style="text-align: center; width: 50%; border: none;">
                        _____________________<br>
                        Director(a) del Centro Educativo
                    </td>
                    <td style="text-align: center; width: 50%; border: none;">
                        _____________________<br>
                        Persona Responsable
                    </td>
                </tr>
            </table>
        </div>

        @if ($index < count($boletas) - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
