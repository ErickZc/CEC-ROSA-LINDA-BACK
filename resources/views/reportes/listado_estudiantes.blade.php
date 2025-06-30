<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Estudiantes</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .logo { width: 100px; }
        .header { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
        <h2>Listado de Estudiantes</h2>
        <p>Grado: {{ $grado->nombre }}, Sección: {{ $seccion }}</p>
        <p>Fecha de generación: {{ $fecha }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>NIE</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estudiantes as $estudiante)
                <tr>
                    <td>{{ $estudiante['nie'] }}</td>
                    <td>{{ $estudiante['nombre'] }}</td>
                    <td>{{ $estudiante['apellido'] }}</td>
                    <td>{{ $estudiante['estado'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
