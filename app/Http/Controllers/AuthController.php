<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /*public function login(Request $request)
    {
        $credentials = $request->only('correo', 'password');

        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'usuario' => Auth::user()
        ]);
    }*/

    public function me()
    {
        $usuario = \App\Models\Usuario::with('rol')->find(auth()->id());

        return response()->json([
            'id_usuario' => $usuario->id_usuario,
            'usuario' => $usuario->usuario,
            'id_persona' => $usuario->id_persona,
            'rol' => $usuario->rol->rol ?? null,
        ]);
        /*return response()->json([
        'message' => 'funciona correctamente',
        'user' => auth()->user()
    ]);*/
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function login(Request $request)
    {
        $correo = $request->input('correo');
        $password = $request->input('password');
    
        $usuario = Usuario::with(['persona', 'rol'])->where('correo', $correo)->first();
    
        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario no existe'
            ], 404);
        }
    
        if ($usuario->estado !== 'ACTIVO') {
            return response()->json([
                'message' => 'El usuario no está activo'
            ], 403); // 403 Forbidden
        }
    
        if (!Hash::check($password, $usuario->password)) {
            return response()->json([
                'message' => 'Contraseña incorrecta'
            ], 401);
        }

        $customClaims = [
            'rol' => $usuario->rol->rol ?? 'INVITADO',
            'nombre' => $usuario->persona->nombre ?? 'SinNombre',
            'apellido' => $usuario->persona->apellido ?? 'SinApellido',
            'usuario' => $usuario->usuario,
            'id_persona' => $usuario->id_persona,
            'id_rol' => $usuario->rol->id_rol,
        ];

        $token = JWTAuth::claims($customClaims)->fromUser($usuario);

         return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'usuario' => $usuario,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function validarCorreo(Request $request)
    {
        $correo = $request->input('correo');
        $usuario = Usuario::where('correo', $correo)->select('id_usuario', 'estado')->first();
    
        if (!$usuario) {
            return response()->json([
                'message' => 'El correo no ha sido encontrado'
            ], 404);
        }
    
        if ($usuario->estado !== 'ACTIVO') {
            return response()->json([
                'message' => 'El correo no está activo'
            ], 403); // 403 Forbidden
        }
       
        return response()->json([
            'message' => 'El usuario a sido encontrado',
            'usuario' => $usuario
        ], 200);
    }

    public function validarCorreoCoordinador(Request $request)
    {
        $correo = $request->input('correo');
        $usuario = Usuario::where('correo', $correo)
            ->where('id_rol', '!=', 1) // 1 = ADMINISTRADOR
            ->select('id_usuario', 'estado')
            ->first();
    
        if (!$usuario) {
            return response()->json([
                'message' => 'El correo no ha sido encontrado'
            ], 404);
        }
    
        if ($usuario->estado !== 'ACTIVO') {
            return response()->json([
                'message' => 'El correo no está activo'
            ], 403); // 403 Forbidden
        }
       
        return response()->json([
            'message' => 'El usuario a sido encontrado',
            'usuario' => $usuario
        ], 200);
    }

    public function actualizarCredenciales(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'nueva_contrasena' => 'required|string|min:6'
        ]);

        $usuario = Usuario::where('correo', $request->correo)->first();

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->password = Hash::make($request->nueva_contrasena);
        $usuario->save();

        return response()->json(['message' => 'Contraseña actualizada con éxito'], 200);
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token inválido'], 401);
        }
    }
}
