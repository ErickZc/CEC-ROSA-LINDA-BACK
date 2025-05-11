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
            <p>Hola,</p>
            <p>Tu código OTP es:</p>
            <h2 style='color: #00466a;'>$otp</h2>
            <p>Este código es válido por 5 minutos.</p>
            <p>Saludos,<br>Soporte Técnico</p>
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

        $htmlContent = '
            <div style="font-family: sans-serif; color: #1F2937; background-color: #F9FAFB; padding: 20px; border-radius: 8px;">
                <p style="font-size: 16px; margin-bottom: 16px;">Hola,</p>
                <p style="font-size: 16px; margin-bottom: 16px;">
                    Nuestro administrador del sistema ha cambiado tus credenciales el día <strong>' . $date->translatedFormat('j \\d\\e F Y') . '</strong>, 
                    a las <strong>' . $date->format('g:i A')  . '</strong> hora El Salvador.
                </p>
                <p style="font-size: 16px; margin-bottom: 16px;">Este mensaje es solamente informativo.</p>
                <p style="font-size: 16px;">Saludos,<br><span style="font-weight: bold;">Soporte Técnico</span></p>
            </div>
        ';



        Mail::html($htmlContent, function ($message) use ($recipient_email,$date) {
            $message->to($recipient_email)
                    ->subject('Actualización de contraseña - ' . $date->translatedFormat('j \\d\\e F Y'));
        });

        return response()->json([
            'message' => 'Email enviado al correo ' . $recipient_email
            //'otp' => $otp // mostrar solo para pruebas, remueve en producción
        ]);
    }

}
