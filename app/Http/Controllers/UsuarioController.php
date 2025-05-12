<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\RolUsuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{

    public function index(Request $request)
    {
        // Obtener el parámetro de búsqueda, si existe
        $search = $request->input('search', '');

        // Filtrar los usuarios según el parámetro de búsqueda
        $usuarios = Usuario::with('rol')
                            ->where('usuario', 'like', "%{$search}%")
                            ->orWhere('correo', 'like', "%{$search}%")
                            ->paginate(10);

        // Devolver los usuarios paginados
        return response()->json($usuarios);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'correo' => 'required|email|unique:Usuario,correo', // Valida correo único
            'usuario' => 'required|string|unique:Usuario,usuario', // Valida nombre de usuario único
            'password' => 'required|string',
            'id_rol' => 'required|integer',
            'id_persona' => 'required|integer',
        ]);

        $usuario = Usuario::create([
            'usuario' => $validated['usuario'],
            'password' => Hash::make($validated['password']),
            'correo' => $validated['correo'],
            'id_rol' => $validated['id_rol'],
            'id_persona' => $validated['id_persona']
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'data' => $usuario
        ]);
    }
  
    public function login(Request $request)
    {
        $correo = $request->input('correo');
        $password = $request->input('password');
    
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
    
        if (!Hash::check($password, $usuario->password)) {
            return response()->json([
                'message' => 'Contraseña incorrecta'
            ], 401);
        }
    
        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'usuario' => $usuario
        ], 200);
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

        return response()->json(['message' => 'Contraseña actualizada con éxito']);
    }

     public function update(Request $request, $id)
     {
         // Buscar usuario
         $usuario = Usuario::find($id);
         if (!$usuario) {
             return response()->json(['message' => 'Usuario no encontrado'], 404);
         }
     
         // Asignar valores directamente desde el request
         $usuario->usuario = $request->input('usuario');
         $usuario->correo = $request->input('correo');
         $usuario->id_rol = $request->input('id_rol');
         $usuario->id_persona = $request->input('id_persona');
     
         // Verificar si se envió la contraseña y no está vacía
         if ($request->filled('password')) {
             $usuario->password = Hash::make($request->input('password'));
         }
     
         $usuario->save();
     
         return response()->json(['message' => 'Usuario actualizado correctamente', 'usuario' => $usuario], 200);
     }


    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    public function usuariosPorRol()
    {
        try {
            $roles = RolUsuario::withCount('usuarios')->get();

            $result = $roles->map(function ($rol) {
                return [
                    'rol' => $rol->rol,
                    'total' => $rol->usuarios_count
                ];
            });

            return response()->json([
                'message' => 'Usuarios agrupados por rol obtenidos con éxito',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los usuarios por rol',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function totalUsuarios()
    {
        try {
            $total = \App\Models\Usuario::count(); // Asegúrate de que el modelo Usuario esté bien referenciado

            return response()->json([
                'message' => 'Total de usuarios obtenido con éxito',
                'data' => ['total' => $total]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el total de usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}
