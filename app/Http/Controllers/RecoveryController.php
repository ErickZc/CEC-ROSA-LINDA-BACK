<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class RecoveryController extends Controller
{
    public function sendOtp(Request $request)
    {
        $otp = rand(1000, 9999); // OTP aleatorio
        $recipient_email = $request->correo;
        $id_usuario = $request->id_usuario;

        $htmlContent = "
            <div style='font-family: Arial, sans-serif; background-color: #f4f7fa; padding: 30px;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>

            <div style='background-color: #1e1e1e; color: white; text-align: center; padding:10px 10px;'>
                <table align='center' cellpadding='0' cellspacing='0' border='0' style='margin: 20px auto;'>
                    <tr>
                        <td align='center' valign='middle' style='padding-right: 10px;'>
                            <img src='https://icons.veryicon.com/png/o/business/cloud-server-cvm-icon/network-security.png' width='90' height='90' alt='Icono' style='display: block;'>
                        
                            </td>
                        <td align='center' valign='middle'>
                            <h1 style='margin: 0; font-size: 26px;'>Solicitud de cambio de credenciales</h1>
                        </td>
                    </tr>
                </table>
            </div>

            <div style='padding: 30px; text-align: center;'>
                <p style='font-size: 16px; color: #333333;'>
                    Has solicitado realizar un cambio de contraseña. Para continuar, por favor usa el siguiente código en nuestra aplicación:
                </p>

                <div style='margin: 30px auto; display: inline-block; background-color: #f0f0f0; padding: 20px 40px; border-radius: 8px;'>
                    <h2 style='margin: 0; font-size: 32px; color: #1e1e1e; letter-spacing: 2px;'>$otp</h2>
                </div>

                <p style='font-size: 14px; color: #666666; margin-top: 20px;'>
                    Este código es válido por <strong>5 minutos</strong>.
                </p>

                <p style='font-size: 14px; color: #666666; margin-top: 20px;'>
                    Si no solicitaste esta acción, puedes ignorar este mensaje.
                </p>
            </div>

            <div style='background-color: #f4f4f4; text-align: center; padding: 20px; font-size: 13px; color: #888888;'>
                Atentamente,<br><strong>Equipo de Soporte Técnico</strong>
            </div>
        </div>
    </div>
        ";

        Mail::html($htmlContent, function ($message) use ($recipient_email) {
            $message->to($recipient_email)
                    ->subject('Código de verificación OTP');
        });

        $otp = Otp::create([
            'codigo' => $otp,
            'fecha_hora' => Carbon::now(),
            'id_usuario' => $id_usuario
        ]);

        return response()->json([
            'message' => 'OTP enviado correctamente a ' . $recipient_email
            //'otp' => $otp // mostrar solo para pruebas, remueve en producción
        ]);
    }

    public function emailCambioPassword(Request $request)
    {
        setlocale(LC_TIME, 'es_ES.UTF-8'); // Para sistemas Unix/Linux
        Carbon::setLocale('es');
        $date = Carbon::now('America/El_Salvador');


        $recipient_email = $request->correo;

        $htmlContent = <<<HTML
            <div style="font-family: sans-serif; background-color: #F9FAFB; border-radius: 10px; color: #1F2937; max-width: 600px; margin: auto;">
                <div style="background-color: #0b6e06; color: white; text-align: center; padding: 5px; border-radius: 10px 10px 0 0;">
                    <table align="center" cellpadding="0" cellspacing="0" border="0" style="margin: 20px auto;">
                        <tr>
                            <td align="center" valign="middle" style="padding-right: 10px;">
                                <img src="https://cdn-icons-png.flaticon.com/512/9028/9028001.png" width="90" height="90" alt="Icono" style="display: block;">
                            </td>
                            <td align="center" valign="middle">
                                <h1 style="margin: 0; font-size: 26px;">Actualización de credenciales</h1>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style="padding: 24px;">
                    <p style="font-size: 16px; margin-bottom: 16px;">Hola,</p>

                    <p style="font-size: 16px; margin-bottom: 16px;">
                        Queremos informarte que tus <strong>credenciales de acceso</strong> fueron actualizadas correctamente por el administrador del sistema.
                    </p>

                    <div style="background-color: #E5E7EB; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="margin: 0; font-size: 16px;">
                            📅 <strong>Fecha:</strong> {$date->translatedFormat('j \\d\\e F Y')}<br>
                            🕒 <strong>Hora:</strong> {$date->format('g:i A')} <span style="font-size: 14px;">(hora El Salvador)</span>
                        </p>
                    </div>

                    <p style="font-size: 16px; margin-bottom: 16px;">
                        Este mensaje es solo informativo. Si tú no solicitaste esta acción o necesitas asistencia, por favor contacta a nuestro equipo de soporte.
                    </p>
                    <p style="font-size: 16px; margin-bottom: 16px;">
                        Por favor, no respondas a este correo.
                    </p>
                </div>
                <div style="background-color: #f4f4f4; text-align: center; padding: 20px; font-size: 13px; color: #888888; border-radius: 0 0 10px 10px;">
                    Atentamente,<br><strong>Equipo de Soporte Técnico</strong>
                </div>
            </div>
        HTML;




        Mail::html($htmlContent, function ($message) use ($recipient_email,$date) {
            $message->to($recipient_email)
                    ->subject('Actualización de contraseña - ' . $date->translatedFormat('j \\d\\e F Y'));
        });

        

        return response()->json([
            'message' => 'Email enviado al correo ' . $recipient_email
            //'otp' => $otp // mostrar solo para pruebas, remueve en producción
        ], 200);
    }

}
