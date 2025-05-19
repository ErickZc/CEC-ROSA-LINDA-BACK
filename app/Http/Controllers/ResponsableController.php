<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Usuario;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResponsableController extends Controller
{
    public function allEstudiantes()
    {
        return Responsable::all();
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $responsables = Responsable::with([
            'persona.usuario'
        ])
        ->where('estado', 'ACTIVO')
        ->whereHas('persona', function ($query) use ($search) {
            $query->where('nombre', 'like', "%{$search}%")
                ->orWhere('apellido', 'like', "%{$search}%")
                ->orWhereHas('usuario', function ($q) use ($search) {
                    $q->where('usuario', 'like', "%{$search}%")
                    ->orWhere('correo', 'like', "%{$search}%");
                });
        })
        ->paginate(10);

        return response()->json($responsables);
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


        $validated = Validator::make($request->all(), $rules)->validate();
        $validated['id_rol'] = 4;

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

            Responsable::create([
                'id_persona' => $persona->id_persona,
                'estado'     => 'ACTIVO',
            ]);

            //Crear el usuario
            $usuario = Usuario::create([
                'usuario'   => $validated['usuario'],
                'password'  => Hash::make($validated['password']),
                'correo'    => $validated['correo'],
                'id_rol'    => $validated['id_rol'],
                'id_persona'=> $persona->id_persona,
                'estado'    => 'ACTIVO',
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

    public function update(Request $request, $id)
    {
        // Validación
        $validated = $request->validate([
            'nombre'    => 'required|string|max:50',
            'apellido'  => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:15',
            'genero'    => 'required|in:MASCULINO,FEMENINO,OTRO',
            
            'password'  => 'nullable|string|min:8',

        ]);

        // Obtener al docente y su relación con persona
        $responsables = Responsable::findOrFail($id);
        $persona = $responsables->persona;

        // Actualizar persona
        $persona->nombre    = $validated['nombre'];
        $persona->apellido  = $validated['apellido'];
        $persona->direccion = $validated['direccion'] ?? $persona->direccion;
        $persona->telefono  = $validated['telefono'] ?? $persona->telefono;
        $persona->genero    = $validated['genero'];
        $persona->save();

        // Buscar usuario relacionado
        $usuario = Usuario::where('id_persona', $persona->id_persona)->firstOrFail();

        if (!empty($validated['password'])) {
            $usuario->password = Hash::make($validated['password']);
        }

        $usuario->save();

        return response()->json([
            'message' => 'Encargado actualizado correctamente',
            'data' => [
                'usuario' => $usuario->load('persona', 'rol'),
                'responsable' => $responsables
            ]
        ]);

    }

    public function destroy($id)
    {
        $responsables = Responsable::find($id);

        if (!$responsables) {
            return response()->json(['message' => 'Encargado no encontrado'], 404);
        }

        $responsables->estado = 'INACTIVO';
        $responsables->save();

        $usuario = Usuario::where('id_persona', $responsables->id_persona)->first();
        if ($usuario && $usuario->estado !== 'INACTIVO') {
            $usuario->estado = 'INACTIVO';
            $usuario->save();
        }

        return response()->json(['message' => 'Encargado eliminado correctamente']);
    }
}
