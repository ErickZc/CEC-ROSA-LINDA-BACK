<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RolUsuarioController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\HistorialEstudianteController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\DocenteMateriaGradoController;
use App\Http\Controllers\InasistenciaController;
use App\Http\Controllers\RecoveryController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// Middleware con llave privada para consumo de API
Route::middleware('api.key')->group(function () {
    
    Route::get('/docentes', [DocenteController::class, 'index']);
    Route::get('/personas', [PersonaController::class, 'index']);
    Route::get('/roles', [RolUsuarioController::class, 'index']);
    Route::get('/secciones', [SeccionController::class, 'index']);
    Route::get('/grados', [GradoController::class, 'index']);
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/estudiantes', [EstudianteController::class, 'index']);
    Route::get('/historial', [HistorialEstudianteController::class, 'index']);
    Route::get('/materias', [MateriaController::class, 'index']);
    Route::get('/periodos', [PeriodoController::class, 'index']);
    Route::get('/notas', [NotaController::class, 'index']);
    Route::get('/doc_mat_grad', [DocenteMateriaGradoController::class, 'index']);
    Route::get('/inasistencias', [InasistenciaController::class, 'index']);
    Route::get('/reporte-inasistencias', [InasistenciaController::class, 'getInasistenciaReport']);

    //Ruta para insertar registros
    Route::post('/usuarios', [UsuarioController::class, 'store']);

    //Ruta para eliminar registros
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);

    //Ruta para editar registros
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);

    // Rutas para el login
    Route::post('/login', [UsuarioController::class, 'login']);
    Route::get('/test', function () {
        return response()->json(['message' => 'API funcionando correctamente']);
    });

    // Ruta para envio de correos - Token OTP
    Route::post('/enviar-otp', [RecoveryController::class, 'sendOTP']);

    // Ruta para validar existencia de un correo
    Route::post('/validarCorreo', [UsuarioController::class, 'validarCorreo']);
    // Ruta para validar credenciales
    Route::post('/actualizar-credenciales', [UsuarioController::class, 'actualizarCredenciales']);
    // Ruta para validar token OTP
    Route::post('/validarToken', [OtpController::class, 'validarToken']);
    // Ruta para leer token OTP en la base de datos
    Route::post('/leerToken', [OtpController::class, 'leerToken']);

    Route::post('/send-otp', [RecoveryController::class, 'sendOTP']);

    Route::post('/emailCambioPassword', [RecoveryController::class, 'emailCambioPassword']);
    //Reportes
    Route::get('/usuariosPorRol', [UsuarioController::class, 'usuariosPorRol']);
    Route::get('/totalUsuarios', [UsuarioController::class, 'totalUsuarios']);


    // Rutas para el chat
    Route::post('/chatbot', [ChatController::class, 'chatbot']);
});