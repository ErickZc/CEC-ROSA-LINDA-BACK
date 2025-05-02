<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Persona;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{

    public function index(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $search = $request->input('search', '');

        // Filtrar los usuarios según el parámetro de búsqueda
        $usuarios = Usuario::where('usuario', 'like', "%{$search}%")
                            ->orWhere('correo', 'like', "%{$search}%")
                            ->paginate(10);

        // Devolver los usuarios paginados
        return response()->json($usuarios);
    }


    public function store(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string|confirmed',
            'correo' => 'required|email|unique:usuarios,correo',
            'id_rol' => 'required|exists:roles,id',
            'id_persona' => 'required|exists:personas,id',
        ]);

        $usuario = new Usuario();
        $usuario->usuario = $request->usuario;
        $usuario->password = bcrypt($request->password);
        $usuario->correo = $request->correo;
        $usuario->id_rol = $request->id_rol;
        $usuario->id_persona = $request->id_persona;
        $usuario->save();

        return response()->json(['message' => 'Usuario creado con éxito'], 201);
    }
  
     public function login(Request $request)
     {
         $correo = $request->input('correo');
         $pass = $request->input('password');
     
         //$usuario = Usuario::where('correo', $correo)->first();
         $usuario = Usuario::with('persona')->where('correo', $correo)->first();
     
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
     
         if ($pass !== $usuario->password) {
             return response()->json([
                 'message' => 'Contraseña incorrecta'
             ], 401);
         }
     
         return response()->json([
             'message' => 'Inicio de sesión exitoso',
             'usuario' => $usuario
         ], 200);
     }

    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        //
    }
}
