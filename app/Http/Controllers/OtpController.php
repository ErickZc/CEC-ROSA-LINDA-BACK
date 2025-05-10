<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Otp;
use Carbon\Carbon;

class OtpController extends Controller
{
    public function validarToken(Request $request)
    {
        $id_usuario = $request->id_usuario;
        $limite = 3; // Límite de intentos
        $minutosBloqueo = 30;
        
        // Verificar si hay 3 o más tokens en los últimos 30 minutos
        $tokens = Otp::where('id_usuario', $id_usuario)
        ->where('fecha_hora', '>=', Carbon::now()->subMinutes($minutosBloqueo))
        ->orderBy('fecha_hora', 'desc')
        ->get();

        if ($tokens->count() >= $limite) {
            $ultimoToken = $tokens->first();
            $siguienteIntento = Carbon::parse($ultimoToken->fecha_hora)->addMinutes($minutosBloqueo);
        
            $ahora = now();
            $diferencia = $ahora->diff($siguienteIntento);
        
            return response()->json([
                'message' => 'Has excedido el límite de intentos. Inténtalo más tarde.',
                'restante_minutos' => $diferencia->i,
                'restante_segundos' => $diferencia->s,
            ], 429);
        }

        return response()->json([
            'message' => 'Validación exitosa. Puedes generar o validar un nuevo token.'
        ], 200);

    }

    public function leerToken(Request $request)
    {
        $expiracion = 5; // minutos
        $id_usuario = $request->id_usuario;
        $token = $request->token;

        $otp = Otp::where('id_usuario', $id_usuario)
                  ->orderBy('fecha_hora', 'desc')
                  ->first();

        if($token != $otp->codigo){
            return response()->json([
                'message' => 'El token no es correcto.',
                'codigo' => 'invalido'
            ], 401);
        }

        if($otp->fecha_hora < Carbon::now()->subMinutes($expiracion)){
            return response()->json([
                'message' => 'El token ha expirado.',
                'codigo' => 'expirado'
            ], 401);
        }

        // Si el token es correcto y no ha expirado, se puede proceder

        return response()->json([
            'message' => 'El token ha sido leido con exito.'
        ], 200);

    }
}
