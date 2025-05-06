<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RecoveryController extends Controller
{
    public function sendOtp(Request $request)
    {
        $otp = rand(1000, 9999); // OTP aleatorio
        $recipient_email = $request->correo;

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

        return response()->json([
            'message' => 'OTP enviado correctamente a ' . $recipient_email,
            'otp' => $otp // mostrar solo para pruebas, remueve en producción
        ]);
    }

}
