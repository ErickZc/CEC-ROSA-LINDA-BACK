<div style="font-family: sans-serif;color:black; padding: 20px;">
    <p>Estimado/a {{ $responsable }},</p>
    <p>Le informamos que su hijo/a <strong>{{ $estudiante }}</strong> tuvo una inasistencia el día <strong>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</strong>.</p>
    <p>Este mensaje es automático. Para más información, comuníquese con la institución.</p>
    <br>
    <p>Atentamente,<br>Centro Educativo Col. Rosa Linda</p>
</div>