<div style="margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #ebeced; color: #F7F7F7;">
    <div style="max-width: 700px; margin: 20px auto; background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

        <div style="height: 6px; background-color: #1447E6;"></div>

        <div style="text-align: center; padding: 30px 20px 20px 20px; border-bottom: 1px solid #EBECED;">
            <img src="logo.png" alt="Logo Centro Educativo" style="width: 80px; height: auto; margin: 0 auto 15px; display: block;">
            <h2 style="margin: 0; color: #111828; font-size: 20px; font-weight: 600;">Notificación de Inasistencia</h2>
            <p style="margin: 8px 0 0 0; color: #666; font-size: 14px;">Centro Educativo Col. Rosa Linda</p>
        </div>

        <div style="padding: 25px;">
            <p style="margin: 0 0 18px 0; font-size: 16px; color: #4b5563;">
                Estimado/a {{ $responsable }},,
            </p>

            <p style="margin: 0 0 18px 0; font-size: 16px; line-height: 1.5; color: #4b5563;">
                Esperamos que se encuentre muy bien. Por medio de este mensaje, queremos informarle que su hijo/a <span style="color: #1447E6; font-weight: 600;">{{ $estudiante }}</span> tuvo una inasistencia el día <span style="color: #1447E6; font-weight: 600;">{{\Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>.
            </p>
            <p style="margin: 0 0 18px 0; font-size: 16px; line-height: 1.5; color: #4b5563;">La puntualidad y
                asistencia son fundamentales para el buen desarrollo académico. Le solicitamos, por favor, estar
                pendiente de la asistencia de su hijo(a) y, en caso de presentarse alguna situación que impida su
                asistencia, solicitar el permiso correspondiente por medio del sistema.</p>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #EBECED;">
            <p style="margin: 0 0 5px 0; font-size: 16px; color: #111828; font-weight: 600;">
                Atentamente,
            </p>
            <p style="margin: 0; font-size: 14px; color: #666;">
                Centro Educativo Col. Rosa Linda
            </p>
        </div>

        <div style="background-color: #111828; color: white; padding: 15px; text-align: center;">
            <p style="font-size: 14px; color: #666; font-style: italic;">
                Este mensaje es automático. Para más información, comuníquese con la institución.
            </p>
        </div>
    </div>
</div>