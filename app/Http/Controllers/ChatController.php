<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function chatbot(Request $request)
    {
        $userMessage = $request->input('message');

        if (!$userMessage) {
            return response()->json(['error' => 'Falta el mensaje del usuario'], 400);
        }

        try {
            // Leer los archivos de temas y respuestas
            $temas = file(storage_path('app/public/temas_escolares.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $respuestas = file(storage_path('app/public/respuestas.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Validar que ambos tengan la misma cantidad de líneas
            if (count($temas) !== count($respuestas)) {
                return response()->json(['error' => 'Los archivos de temas y respuestas no coinciden en longitud.'], 500);
            }

            // Normalizar el mensaje del usuario
            $userMessageNormalized = strtolower(trim($userMessage));
            $responseMessage = 'Lo siento, no tengo información sobre eso.';

            foreach ($temas as $index => $tema) {
                if (stripos(strtolower($tema), $userMessageNormalized) !== false) {
                    $responseMessage = "Aquí tienes la información sobre el tema:<br>" . $respuestas[$index];
                    break;
                }
            }

            return response()->json(['reply' => $responseMessage]);

        } catch (\Exception $e) {
            Log::error('Error al procesar el chatbot:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Excepción: ' . $e->getMessage()], 500);
        }
    }

    public function temas()
    {
        try {
            // Cargar el archivo de temas
            $temas = Storage::disk('public')->get('temas_escolares.txt');
            
            // Devolver los temas como respuesta JSON
            return response()->json(['temas' => $temas]);
        } catch (\Exception $e) {
            // Manejar errores
            return response()->json(['error' => 'No se pudo cargar el contenido del archivo.'], 500);
        }
    }


}
