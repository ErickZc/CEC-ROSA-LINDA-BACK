<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Notas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        th, td {
            border: 1px solid #000;
            padding: 3px;
            height: 15px;
            width: 20px;
            text-align: center;
        }

        .header td {
            border: none;
            font-weight: bold;
        }

        .bg-orange {
            background-color: #FBE4D5;
        }

        .bg-blue {
            background-color: #DAEEF3;
        }

        .bg-gray {
            background-color: #EDEDED;
        }

        .title {
            font-size: 14px;
            text-align: center;
            font-weight: bold;
        }

        .small {
            font-size: 8px;
        }

        .align-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .vertical-header {
            width: 20px;
            text-align: center;
            vertical-align: middle;
            padding: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        .vertical-word {
            padding: 0;
            width: 30px;
            text-align: center;
            vertical-align: middle;
        }

        .vertical-table {
            display: table;
            margin: 0 auto;
        }

        .vertical-row {
            display: table-row;
        }

        .vertical-row span {
            display: table-cell;
            width: 10px;  /* puedes ajustar */
            text-align: center;
            font-size: 9px;
            padding: 0;
        }

        .header-text-number{
            width: 30px;
        }

        .header-text-name{
            width: 200px;
        }
        
        .fila-par {
            background-color:rgb(220, 217, 217);
        }
        
        .fila-impar {
            background-color:rgb(255, 255, 255);
        }

        .promedio-alto {
            color: green;
        }

        .promedio-bajo {
            color: red;
        }

        .nombre-reporte {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

    </style>
</head>
<body>
    <div style="position: relative; margin-bottom: 5px; height: 65px;">
        <img src="images/logo.jpg" alt="Logo" style="height: 65px; position: absolute; left: 0; top: 0;">
        <div class="nombre-reporte">Reporte de Notas</div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="5" class="header-text-number">Nº</th>
                <th colspan="5" class="align-left" style="font-weight: normal !important;">Docente: <strong>{{ $docente }}</strong></th>
                <th colspan="5" class="align-left" style="font-weight: normal !important;">Asignatura: <strong>{{ $materia }}</strong></th>
                <th colspan="3" class="align-left" style="font-weight: normal !important;">Grado: <strong>{{ $grado }}</strong></th>
                <th colspan="3" class="align-left" style="font-weight: normal !important;">Sección: <strong>"{{ $seccion }}"</strong></th>
            </tr>
            <tr>
                <th rowspan="4" colspan="6" class="header-text-name">Alumnos</th>
                <th colspan="4" class="align-left" style="font-weight: normal !important;">Trimestre: <strong>{{ $periodo }}</th>
                <th colspan="6">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>{{ $institucion }}</span>
                        <span>{{ $anio }}</span>
                    </div>
                </th>
            </tr>
            <tr>
                <th colspan="3">act. Cotidiana<br><span>(35%)</span></th>
                <th rowspan="3" class="vertical-header bg-orange">P<br>R<br>O<br>M</th>
                <th rowspan="3">actividad Integradora<br><span>(35%)</span></th>
                <th rowspan="3" class="vertical-header bg-orange">P<br>R<br>O<br>M</th>
                <th rowspan="3" colspan="2">prueba objetiva<br><span>(30%)</span></th>
                <th rowspan="3" class="vertical-header bg-orange">P<br>R<br>O<br>M</th>
                <th rowspan="3" class="vertical-word bg-orange">
                    <div class="vertical-table">
                        <div class="vertical-row"><span> </span><span>F</span></div>
                        <div class="vertical-row"><span>P</span><span>I</span></div>
                        <div class="vertical-row"><span>R</span><span>N</span></div>
                        <div class="vertical-row"><span>O</span><span>A</span></div>
                        <div class="vertical-row"><span>M</span><span>L</span></div>
                        <div class="vertical-row"><span> </span><span> </span></div>
                    </div>
                </th>
                <!-- <th colspan="3">Proceso de recuperación<br><span>(35%)</span></th> -->
            </tr>
            <tr>
                <th>Act.</th>
                <th>Act.</th>
                <th>Act.</th>
                <!-- <th rowspan="2">R</th>
                <th rowspan="2">PR</th>
                <th rowspan="2">PF</th> -->
            </tr>
            <tr>
                <th>10%</th>
                <th>10%</th>
                <th>15%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estudiantes as $index => $e)
                @foreach ($e['notas'] as $nota)
                    @php
                        $act1 = $nota['actividad1'] ?? 0;
                        $act2 = $nota['actividad2'] ?? 0;
                        $act3 = $nota['actividad3'] ?? 0;
                        $actividadProm = number_format(((($act1 + $act2 + $act3) / 3) * 0.4), 2);

                        $actividadInt = $nota['actividadInt'] ?? 0;
                        $actividadIntProm = number_format($actividadInt * 0.3, 2);

                        $examen = $nota['examen'] ?? 0;
                        $examenProm = number_format($examen * 0.3, 2);

                        $promedioGeneral = number_format($nota['promedio'] ?? 0, 2);

                        $limite = $nota_minima;
                        $claseColor = $promedioGeneral >= $limite ? 'promedio-alto' : 'promedio-bajo';
                    @endphp
                    <tr class="{{ $index % 2 === 0 ? 'fila-par' : 'fila-impar' }}">
                        <td>{{ $index + 1 }}</td>
                        <td colspan="6" class="align-left">{{ $e['estudiante']['apellido'] }}, {{ $e['estudiante']['nombre'] }}</td>
                        <td class="act1">{{ $nota['actividad1'] ?? '0.00' }}</td>
                        <td class="act2">{{ $nota['actividad2'] ?? '0.00' }}</td>
                        <td class="act3">{{ $nota['actividad3'] ?? '0.00' }}</td>
                        <td class="prom"><strong>{{ $actividadProm }}</strong></td>
                        <td>{{ $nota['actividadInt'] ?? '0.00' }}</td>
                        <td><strong>{{ $actividadIntProm }}</strong></td>
                        <td colspan="2">{{ $nota['examen'] ?? '0.00' }}</td>
                        <td><strong>{{ $examenProm }}</strong></td>
                        <!-- <td><strong>{{ $nota['promedio'] ?? '0.00' }}</strong></td> -->
                        <td><strong class="{{ $claseColor }}">{{ number_format($nota['promedio'] ?? '0.00', 2) }}</strong></td>
                        <!-- <td></td>
                        <td></td>
                        <td></td> -->
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    <p style="margin-top: 20px; font-weight: bold;">ACTIVIDADES</p>
</body>



</html>
