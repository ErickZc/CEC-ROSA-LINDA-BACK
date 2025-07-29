<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ArchivoBaseConocimiento;
use Illuminate\Support\Carbon;

class AgentAIController extends Controller
{
    public function consulta(Request $request)
    {
        // Validación
        $inputText = $request->input('input_text');
        $sessionId = $request->input('session_id');

        if (!$inputText || !$sessionId) {
            return response()->json(['error' => 'los campos de input_text y session_id son obligatorios'], 400);
        }

        try {
            $response = Http::post('https://n8n-production-1b6f.up.railway.app/webhook/infoquery', [
                'input_text' => $inputText,
                'session_id' => $sessionId
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

    public function consultaPermisos(Request $request)
    {
        // Validación
        $inputText = $request->input('input_text');
        $sessionId = $request->input('session_id');
        $id_persona = $request->input('id_persona');

        if (!$inputText || !$sessionId) {
            return response()->json(['error' => 'los campos de input_text y session_id son obligatorios'], 400);
        }

        try {
            $response = Http::post('https://n8n-production-1b6f.up.railway.app/webhook/infopermisos', [
                'input_text' => $inputText,
                'session_id' => $sessionId,
                'id_persona' => $id_persona
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

    public function consultaDesingThinking(Request $request)
    {
        // Validación
        $inputText = $request->input('input_text');
        $sessionId = $request->input('session_id');

        if (!$inputText || !$sessionId) {
            return response()->json(['error' => 'los campos de input_text y session_id son obligatorios'], 400);
        }

        try {
            $response = Http::post('https://n8n-production-1b6f.up.railway.app/webhook/design-thinking', [
                'input_text' => $inputText,
                'session_id' => $sessionId
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
        $filename = $request->input('filename');
        $document = $request->input('document');
        $username = $request->input('username');

        if (!$document) {
            return response()->json(['error' => 'El documento es obligatorio'], 400);
        }

        setlocale(LC_TIME, 'es_ES.UTF-8');
        Carbon::setLocale('es');
        $date = Carbon::now('America/El_Salvador');
        $timestamp = $date->format('Y-m-d H:i:s');

        try {
            $response = Http::post('https://n8n-production-1b6f.up.railway.app/webhook/uploaddocsrag', [
                'filename' => $filename,
                'username' => $username,
                'document' => $document,
                'fecha' => $timestamp,
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

    public function eliminarDocumentos(Request $request)
    {
        // Validación
        $fileid = $request->input('file_id');

        if (!$fileid) {
            return response()->json(['error' => 'El documento es obligatorio'], 400);
        }

        try {
            $response = Http::post('https://n8n-production-1b6f.up.railway.app/webhook/deletedocs', [
                'id' => $fileid
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

    public function listarDocumentos()
    {
        try {
            $documents = ArchivoBaseConocimiento::where('estado', 'ACTIVO')
                ->paginate(10);

            return response()->json($documents);
        } catch (\Exception $e) {
            return response()->json([
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
