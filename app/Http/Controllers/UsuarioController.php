<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Usuario;
use App\Models\RolUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function allUsuarios()
    {
        return Usuario::all();
    }

    public function index(Request $request,  $idRol)
    {
        $search = $request->input('search', '');

        $usuarios = Usuario::with(['rol', 'persona'])
            ->where('estado', 'ACTIVO')
            ->where('id_rol', $idRol)
            ->where(function ($query) use ($search) {
                $query->where('usuario', 'like', "%{$search}%")
                    ->orWhere('correo', 'like', "%{$search}%")
                    ->orWhereHas('persona', function ($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%");
                    });
            })
            ->paginate(10);

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        // Validación
        $rules = [
            'nombre'    => 'required|string|max:50',
            'apellido'  => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:15',
            'genero'    => 'required|in:MASCULINO,FEMENINO,OTRO',
        ];

        // Usuario general
        $rules['usuario'] = 'required|string|unique:Usuario,usuario';
        $rules['correo'] = 'required|email|unique:Usuario,correo';
        $rules['password'] = 'required|string|min:8';
        $rules['id_rol'] = 'required|in:1,3';


        $validated = Validator::make($request->all(), $rules)->validate();

        DB::beginTransaction();

        try {
            // Crear la persona
            $persona = Persona::create([
                'nombre'    => $validated['nombre'],
                'apellido'  => $validated['apellido'],
                'direccion' => $validated['direccion'] ?? null, 
                'telefono'  => $validated['telefono'] ?? null, 
                'genero'    => $validated['genero'],
            ]);

            $usuario = null;

            //Crear el usuario
            $usuario = Usuario::create([
                'usuario'   => $validated['usuario'],
                'password'  => Hash::make($validated['password']),
                'correo'    => $validated['correo'],
                'id_rol'    => $validated['id_rol'],
                'id_persona'=> $persona->id_persona,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Usuario creado correctamente',
                'data' => $usuario
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear usuario',
                'details' => $e->getMessage()
            ], 500);
        }
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
         $validated = $request->validate([
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            'genero' => 'required|in:MASCULINO,FEMENINO,OTRO',

            'id_rol' => 'required|integer',
            'password' => 'nullable|string|min:8',
        ]);

        // Obtener el usuario con su persona relacionada
        $usuario = Usuario::with('persona')->findOrFail($id);

        // Actualizar datos en persona
        $persona = $usuario->persona;
        $persona->nombre = $validated['nombre'];
        $persona->apellido = $validated['apellido'];
        $persona->direccion = $validated['direccion'] ?? $persona->direccion;
        $persona->telefono = $validated['telefono'] ?? $persona->telefono;
        $persona->genero = $validated['genero'];
        $persona->save();

        // Solo actualizar password si viene en el request
        if (!empty($validated['password'])) {
            $usuario->password = Hash::make($validated['password']);
        }

        $usuario->save();

        return response()->json([
            'message' => 'Usuario y persona actualizados correctamente',
            'data' => $usuario->load('persona', 'rol'),
        ]);
     }


    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $usuario->estado = 'INACTIVO';
        $usuario->save();

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
