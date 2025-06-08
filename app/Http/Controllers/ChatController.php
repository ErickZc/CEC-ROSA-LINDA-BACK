<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chatbot(Request $request)
    {
        $userMessage = strtolower(trim($request->input('message')));

        if (!$userMessage) {
            return response()->json(['error' => 'Falta el mensaje del usuario'], 400);
        }

        // Detectar saludos comunes
        $saludos = ['hola', 'hey', 'buenas', 'buenos días', 'buenas tardes', 'buenas noches'];
        foreach ($saludos as $saludo) {
            if (str_contains($userMessage, $saludo)) {
                return response()->json(['reply' => '¡Hola! ¿En qué puedo ayudarte con respecto a la institución? 😊']);
            }
        }

        // Cargar temas
        try {
            $temasRaw = file_get_contents(public_path('temas_escolares.txt'));
            $temasArray = array_filter(array_map('trim', explode("\n", strip_tags($temasRaw))));
        } catch (\Exception $e) {
            Log::error('No se pudieron cargar los temas', ['error' => $e->getMessage()]);
            return response()->json(['reply' => 'Error al cargar los temas.']);
        }

        // Convertir temas a minúsculas para mejor comparación
        $temasLower = array_map('strtolower', $temasArray);

        // Separar el mensaje del usuario en palabras
        $palabrasMensaje = explode(' ', $userMessage);

        // Verificar si alguna palabra del mensaje coincide con alguna parte de los temas
        $esRelacionado = false;
        foreach ($palabrasMensaje as $palabra) {
            foreach ($temasLower as $tema) {
                if (str_contains($tema, $palabra)) {
                    $esRelacionado = true;
                    break 2; // rompe ambos bucles si encuentra coincidencia
                }
            }
        }

        if (!$esRelacionado) {
            return response()->json([
                'reply' => '🤖 Solo puedo ayudarte con temas relacionados a la institución. Por favor consulta la lista de temas disponibles.'
            ]);
        }

        // Cargar respuestas institucionales
        try {
            $respuestas = file_get_contents(public_path('respuestas.txt'));
        } catch (\Exception $e) {
            Log::error('No se pudieron cargar las respuestas', ['error' => $e->getMessage()]);
            return response()->json(['reply' => 'Error al cargar la información de respuestas.']);
        }

        // Crear contexto para el modelo de IA
        $contexto = strip_tags("Eres un asistente virtual institucional amable y claro. Solo debes responder sobre temas escolares.\n\nTEMAS:\n$temasRaw\n\nRESPUESTAS:\n$respuestas");

        try {
            // Aquí usas Gemini API (Google) para generar la respuesta
            $reply = $this->usarGeminiAPI("Contexto:\n$contexto\n\nUsuario: $userMessage");

            return response()->json(['reply' => $reply]);
        } catch (\Exception $e) {
            Log::error('Error al contactar con Gemini API', ['error' => $e->getMessage()]);
            return response()->json(['reply' => $e->getMessage()]);
        }
    }

    public function temas()
    {
        try {
            $temas = file_get_contents(public_path('temas_escolares.txt'));
            return response()->json(['temas' => $temas]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo cargar el contenido del archivo.'], 500);
        }
    }

    public function usarGeminiAPI($prompt)
    {
        try {
           $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => env('GEMINI_API_KEY'),
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=${import.meta.env.VITE_GEMINI_API_KEY}', [
                'contents' => [[
                    'model' => 'gemini-2.0-flash',
                    'parts' => [['text' => 'Hola']]
                ]]
            ]);


            if (!$response->successful()) {
                Log::error('Error en respuesta Gemini API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return "Error de API: {$response->status()}";
            }

            $data = $response->json();

            if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Respuesta Gemini sin contenido esperado', ['data' => $data]);
                return 'La API respondió sin texto válido.';
            }

            return $data['candidates'][0]['content']['parts'][0]['text'];

        } catch (\Exception $e) {
            Log::error("Error al contactar con Gemini API", ['error' => $e->getMessage()]);
            return 'Ocurrió un error al generar la respuesta: ' . $e->getMessage();
        }
    }

}
