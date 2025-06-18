<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AgentAIController extends Controller
{
    public function consulta(Request $request)
    {
        // Validación
        $inputText = $request->input('inputText');

        if (!$inputText) {
            return response()->json(['error' => 'inputText es obligatorio'], 400);
        }

        try {
            $response = Http::post('https://n8n-production-1b6f.up.railway.app/webhook/infoquery', [
                'inputText' => $inputText
            ]);

            if ($response->successful()) {
                return response()->json([
                    'message' => 'Petición enviada correctamente.',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json(['ERROR' => 'La respuesta del servidor n8n no fue exitosa'], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function importarDocumentos(Request $request)
    {
        // Validación
        $tipo = $request->input('tipo');
        $document = $request->input('document');

        if (!$document) {
            return response()->json(['error' => 'El documento es obligatorio'], 400);
        }

        try {
            $response = Http::post('https://n8n-production-1b6f.up.railway.app/webhook/uploaddocsrag', [
                'tipo' => $tipo,
                'document' => $document,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'message' => 'OK',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json(['ERROR' => 'La respuesta del servidor n8n no fue exitosa'], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
