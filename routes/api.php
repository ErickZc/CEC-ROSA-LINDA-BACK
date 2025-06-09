<?php

use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RecoveryController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\RolUsuarioController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\InasistenciaController;
use App\Http\Controllers\DocenteMateriaGradoController;
use App\Http\Controllers\HistorialEstudianteController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\AuthController;

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

// Rutas para el login
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});
    // Ruta para validar existencia de un correo
Route::post('/validarCorreo', [AuthController::class, 'validarCorreo']);
// Ruta para validar credenciales
Route::post('/actualizar-credenciales', [AuthController::class, 'actualizarCredenciales']);
// Ruta para validar token OTP
Route::post('/validarToken', [OtpController::class, 'validarToken']);
// Ruta para leer token OTP en la base de datos
Route::post('/leerToken', [OtpController::class, 'leerToken']);

Route::post('/send-otp', [RecoveryController::class, 'sendOTP']);

Route::post('/emailCambioPassword', [RecoveryController::class, 'emailCambioPassword']);
// Ruta para envio de correos - Token OTP
Route::post('/enviar-otp', [RecoveryController::class, 'sendOTP']);

// Nueva ruta para refrescar el token
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    $user = auth()->user();
    // o también
    // $user = JWTAuth::parseToken()->authenticate();
    return response()->json($user);
});

// Middleware con llave privada para consumo de API
Route::middleware(['auth:api', 'api.key'])->group(function () {
    
    //Route::get('/docentes', [DocenteController::class, 'index']);
    Route::resource('/responsables', ResponsableController::class)->except(['show']);
    Route::resource('/docentes', DocenteController::class)->except(['show']);
    Route::resource('/personas', PersonaController::class)->except(['show']);
    Route::get('/roles', [RolUsuarioController::class, 'index']);
    Route::resource('/secciones', SeccionController::class)->except(['show']);
    Route::resource('/grados', GradoController::class)->except(['show']);
    Route::resource('/usuarios', UsuarioController::class)->except(['show']);
    Route::resource('/estudiantes', EstudianteController::class)->except(['show']);
    //Route::get('/estudiantes', [EstudianteController::class, 'index']);
    Route::resource('/historial', HistorialEstudianteController::class)->except(['show']);
    //Route::get('/historial', [HistorialEstudianteController::class, 'index']);
    Route::resource('/materias', MateriaController::class)->except(['show']);
    Route::get('/periodos', [PeriodoController::class, 'index']);
    Route::get('/notas', [NotaController::class, 'index']);
    Route::get('/doc_mat_grad', [DocenteMateriaGradoController::class, 'index']);
    Route::get('/inasistencias', [InasistenciaController::class, 'index']);
    Route::get('/reporte-inasistencias', [InasistenciaController::class, 'getInasistenciaReport']);
    Route::get('/reporte-inasistencias-count', [InasistenciaController::class, 'getInasistenciaCount']);
    Route::get('/reporte-inasistencias-days', [InasistenciaController::class, 'getInasistenciaByDays']);
    Route::get('/gradosList', [GradoController::class, 'gradosList']);
    Route::get('/reporte-inasistencias-default', [InasistenciaController::class, 'getInasistenciaInfoDefault']);

    //Ruta para cargar select
    Route::get('/usuarios/rol/{idRol}', [UsuarioController::class, 'index']);
    Route::get('/usuarios/all', [UsuarioController::class, 'allUsuarios']);
    Route::get('/estudiantes/all', [EstudianteController::class, 'allEstudiantes']);
    Route::get('/docentes/all', [DocenteController::class, 'allDocentes']);
    Route::get('/personas/all', [PersonaController::class, 'allPersonas']);
    Route::get('/secciones/all', [GradoController::class, 'allSecciones']);
    Route::get('/seccion/all', [SeccionController::class, 'allSecciones']);
    Route::get('/grados/all', [GradoController::class, 'allGrados']);
    Route::get('/materias/all', [MateriaController::class, 'allMaterias']);
    Route::get('/notas/all', [NotaController::class, 'allNotas']);
    Route::get('/periodos/all', [PeriodoController::class, 'allPeriodos']);
    Route::get('/roles/all', [RolUsuarioController::class, 'allRoles']);

    //Reportes
    Route::get('/usuariosPorRol', [UsuarioController::class, 'usuariosPorRol']);
    Route::get('/totalUsuarios', [UsuarioController::class, 'totalUsuarios']);
    Route::get('/estudiantes/reporte/{idGrado}', [EstudianteController::class, 'contarEstudiantesPorSeccion']);
    Route::get('/estudiantes/reporteEstudiantes/{idGrado}/{idMateria}/{idSeccion}', [EstudianteController::class, 'reporteEstudiantes']);
    Route::get('/estudiantes/reporteRepetidores', [EstudianteController::class, 'estudiantesRepetidores']);

    // Rutas para el chat
    Route::post('/chatbot', [ChatController::class, 'chatbot']);
    Route::get('/chatbot/temas', [ChatController::class, 'temas']);

    // Rutas para permisos
    Route::resource('/permisos', PermisosController::class)->except(['show']);
    Route::post('/permisos/permisosPorResponsable', [PermisosController::class, 'getPermisosByResponsable']);
    Route::post('/permisos/permisosPorDocente', [PermisosController::class, 'getPermisosByDocente']);
    Route::post('/estudiantes/estudiantesPorResponsable', [EstudianteController::class, 'estudiantesByResponsable']);
    
    // Rutas para la gestión de notas
    Route::get('/notas', [PeriodoController::class, 'index']);
    Route::get('/notas/Data', [NotaController::class, 'getFormularioData']);
    Route::get('/materias/{id}', [MateriaController::class, 'show']);
    Route::get('/estudiantes/{id}', [EstudianteController::class, 'show']);
    Route::get('/estudiantes/seccion/{id}', [EstudianteController::class, 'searchSeccion']);
    Route::get('/estudiantes/seccion/{idSeccion}/rol/{idRol}', [EstudianteController::class, 'filterDataSecciones']);
    Route::get('/estudiantes/seccion/{idRol}/{idPersona}', [EstudianteController::class, 'seccionesPorUsuario']);
    Route::get('/estudiantes/notas/{idGrado}/{idMateria}/{idSeccion}', [EstudianteController::class, 'estudiantesConNotasFiltrados']);
    Route::get('/estudiantes/notasNew/{idGrado}/{idMateria}/{idSeccion}', [EstudianteController::class, 'estudiantesConNotasFiltradosNew']);
    Route::put('/notas/{id}', [NotaController::class, 'update']);
    Route::post('/notasNew', [NotaController::class, 'store']);
});