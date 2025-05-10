<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chatbot(Request $request)
    {
        $userMessage = $request->input('message');

        if (!$userMessage) {
            return response()->json(['error' => 'Falta el mensaje del usuario'], 400);
        }

        $apiKey = env('API_KEY_CHATBOT');
        if (!$apiKey) {
            return response()->json(['error' => 'API Key no configurada'], 500);
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withOptions([
                'verify' => false, // ⚠️ Solo en desarrollo
            ])
            ->post($url . '?key=' . $apiKey, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userMessage]
                        ]
                    ]
                ]
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withOptions([
                'verify' => false, // ⚠️ Solo en desarrollo
            ])
            ->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userMessage]
                        ]
                    ]
                ]
            ]);


            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sin respuesta';
                return response()->json(['reply' => $reply]);
            }

            return response()->json(['error' => 'Respuesta no exitosa del API', 'details' => $response->body()], 500);
        } catch (\Exception $e) {
            Log::error('Error al contactar a Gemini:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Excepción: ' . $e->getMessage()], 500);
        }
    }
}
