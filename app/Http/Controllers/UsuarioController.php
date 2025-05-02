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
        //
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
