<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Usuario;
use App\Models\Estudiante;
use App\Models\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\HistorialEstudiante;
use Illuminate\Support\Facades\Hash;
use App\Models\ResponsableEstudiante;
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
                'id_persona' => $persona->id_persona,
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


    public function obtenerResponsablesPorNIE(Request $request)
    {
        $nie = $request->input('nie');

        if (!$nie) {
            return response()->json(['message' => 'Debe proporcionar el NIE del estudiante'], 400);
        }

        // Buscar al estudiante
        $estudiante = Estudiante::with('persona')->where('nie', $nie)->first();

        if (!$estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        // Obtener correo del estudiante desde la tabla usuario
        $usuario = Usuario::where('id_persona', $estudiante->id_persona)->first();

        // Obtener historial más reciente
        $historial = HistorialEstudiante::with('grado.seccion')
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->latest('id_historial')
            ->first();

        // Obtener responsables
        $responsables = ResponsableEstudiante::with(['responsable.persona'])
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('estado', 'ACTIVO')
            ->get()
            ->map(function ($relacion) {
                $correo = Usuario::where('id_persona', $relacion->responsable->persona->id_persona)->value('correo');

                return [
                    'id_responsable' => $relacion->id_responsable,
                    'nombre' => $relacion->responsable->persona->nombre,
                    'apellido' => $relacion->responsable->persona->apellido,
                    'direccion' => $relacion->responsable->persona->direccion,
                    'telefono' => $relacion->responsable->persona->telefono,
                    'genero' => $relacion->responsable->persona->genero,
                    'correo' => $correo,
                    'parentesco' => $relacion->parentesco,
                    'estado' => $relacion->estado,
                ];
            });

        return response()->json([
            'estudiante' => [
                'nie' => $nie,
                'nombre' => $estudiante->persona->nombre,
                'apellido' => $estudiante->persona->apellido,
                'direccion' => $estudiante->persona->direccion,
                'genero' => $estudiante->persona->genero,
                'correo' => $estudiante->correo,
                'grado' => $historial?->grado?->grado,
                'seccion' => $historial?->grado?->seccion?->seccion,
                'estado' => $historial?->estado,
            ],
            'responsables' => $responsables,
        ]);
    }


}
