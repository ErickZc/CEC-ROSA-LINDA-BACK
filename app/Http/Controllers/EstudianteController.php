<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allEstudiantes()
    {
        return Estudiante::all();
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $estudiantes = Estudiante::with('persona')
            ->where('estado', 'ACTIVO')
            ->where(function ($query) use ($search) {
                $query->whereHas('persona', function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%");
                })
                    ->orWhere('nie', 'like', "%{$search}%");
            })
            ->paginate(10);

        return response()->json($estudiantes);
    }


    public function estudiantesByResponsable(Request $request)
    {
        $responsable = $request->input('responsable');

        if (!$responsable) {
            return response()->json(['error' => 'Responsable es obligatorio'], 400);
        }

        $anioActual = date('Y');

        $estudiantes = Estudiante::with([
            'responsableEstudiantes.responsable',
            'responsableEstudiantes.estudiante',
            'persona',
            'historialEstudianteActual'
        ])
            ->where('estado', 'ACTIVO')
            ->whereHas('responsableEstudiantes.responsable', function ($query) use ($responsable) {
                $query->where('id_persona', $responsable);
            })
            ->whereHas('historialEstudianteActual')
            ->get();

        return response()->json($estudiantes);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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

        $rules['correo'] = 'required|email|max:50';
        $rules['nie'] = 'required|string|max:10';


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

            Estudiante::create([
                'id_persona' => $persona->id_persona,
                'correo'     => $validated['correo'],
                'estado'     => 'ACTIVO',
                'nie'        => $validated['nie'],
            ]);


            DB::commit();

            return response()->json([
                'message' => 'Estudiante creado correctamente',
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validación
        $rules = [
            'nombre'    => 'required|string|max:50',
            'apellido'  => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:15',
            'genero'    => 'required|in:MASCULINO,FEMENINO,OTRO',
            'correo'    => 'required|email|max:50',
            'nie'       => 'required|string|max:10',
        ];

        $validated = Validator::make($request->all(), $rules)->validate();

        DB::beginTransaction();

        try {
            // Buscar el estudiante
            $estudiante = Estudiante::with('persona')->findOrFail($id);

            // Validar que el NIE no esté duplicado (excepto el mismo registro)
            $existeNie = Estudiante::where('nie', $validated['nie'])
                ->where('id_estudiante', '!=', $estudiante->id_estudiante)
                ->exists();

            if ($existeNie) {
                return response()->json([
                    'error' => 'El número de NIE ya está registrado por otro estudiante.'
                ], 422);
            }

            // Actualizar persona
            $estudiante->persona->update([
                'nombre'    => $validated['nombre'],
                'apellido'  => $validated['apellido'],
                'direccion' => $validated['direccion'] ?? null,
                'telefono'  => $validated['telefono'] ?? null,
                'genero'    => $validated['genero'],
            ]);

            // Actualizar estudiante
            $estudiante->update([
                'correo' => $validated['correo'],
                'nie'    => $validated['nie'],
                // 'estado' => opcional si también se permite cambiar estado
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Estudiante actualizado correctamente',
                'data' => $estudiante
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al actualizar el estudiante',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $estudiantes = Estudiante::find($id);

        if (!$estudiantes) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }

        $estudiantes->estado = 'INACTIVO';
        $estudiantes->save();

        return response()->json(['message' => 'Estudiante eliminado correctamente']);
    }
}
