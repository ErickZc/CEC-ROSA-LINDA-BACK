<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use App\Models\HistorialEstudiante;
use App\Models\ResponsableEstudiante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HistorialEstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function allHistorial()
    {
        return HistorialEstudiante::all();
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $historiales = HistorialEstudiante::with(['estudiante.persona', 'grado.seccion'])
            ->whereHas('estudiante', function ($query) use ($search) {
                $query->where('estado', 'ACTIVO')
                    ->where(function ($q) use ($search) {
                        $q->whereHas('persona', function ($q2) use ($search) {
                            $q2->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%");
                        })
                        ->orWhere('correo', 'like', "%{$search}%")
                        ->orWhere('nie', 'like', "%{$search}%");  // <-- aquí agregamos búsqueda por NIE
                    });
            })
            ->orWhereHas('grado', function ($q) use ($search) {
                $q->where('grado', 'like', "%{$search}%")
                ->orWhereHas('seccion', function ($q2) use ($search) {
                    $q2->where('seccion', 'like', "%{$search}%");
                });
            })
            ->orWhere('anio', 'like', "%{$search}%")
            ->paginate(10);

        return response()->json($historiales);
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

        $rules['id_grado'] = 'required|exists:Grado,id_grado';
        $rules['anio'] = 'required|digits:4|integer|min:2000|max:' . date('Y');

        $rules['id_responsable'] = 'required|exists:Responsable,id_responsable';
        $rules['parentesco']  = 'required|string|max:50';
        

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

            //$usuario = null;

            $estudiante = Estudiante::create([
                'id_persona' => $persona->id_persona,
                'correo'     => $validated['correo'],
                'estado'     => 'ACTIVO',
                'nie'        => $validated['nie'],
            ]);

            // Crear historial del estudiante
            HistorialEstudiante::create([
                'id_estudiante' => $estudiante->id_estudiante,
                'id_grado'      => $validated['id_grado'],
                'anio'          => $validated['anio'],
                'estado'        => 'CURSANDO',
            ]);

            // Crear relación responsable-estudiante
            ResponsableEstudiante::create([
                'id_responsable' => $validated['id_responsable'],
                'id_estudiante'  => $estudiante->id_estudiante,
                'parentesco'     => $validated['parentesco'],
                'estado'         => 'ACTIVO',
            ]);
            

            DB::commit();

            return response()->json([
                'message' => 'Estudiante creado correctamente',
                'data' => $estudiante
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
