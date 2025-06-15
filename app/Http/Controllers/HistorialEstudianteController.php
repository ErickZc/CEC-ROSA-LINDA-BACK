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
            ->where('estado', 'CURSANDO')
            ->whereHas('estudiante', function ($query) {
                $query->where('estado', 'ACTIVO');
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    // Buscar por nombre o apellido de la persona
                    $q->whereHas('estudiante.persona', function ($q2) use ($search) {
                        $q2->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%");
                    })
                    // Buscar por correo o NIE del estudiante
                    ->orWhereHas('estudiante', function ($q3) use ($search) {
                        $q3->where('correo', 'like', "%{$search}%")
                        ->orWhere('nie', 'like', "%{$search}%");
                    })
                    // Buscar por grado o sección
                    ->orWhereHas('grado', function ($q4) use ($search) {
                        $q4->where('grado', 'like', "%{$search}%")
                        ->orWhereHas('seccion', function ($q5) use ($search) {
                            $q5->where('seccion', 'like', "%{$search}%");
                        });
                    })
                    // Buscar por año
                    ->orWhere('anio', 'like', "%{$search}%");
                });
            })
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
        $rules = [
            'id_grado'        => 'required|exists:Grado,id_grado',
            'anio'            => 'required|digits:4|integer|min:2000|max:' . date('Y'),
            'id_responsable'  => 'required|exists:Responsable,id_responsable',
            'parentesco'      => 'required|string|max:50',
        ];

        $validated = Validator::make($request->all(), $rules)->validate();

        DB::beginTransaction();

        try {
            // Buscar al estudiante
            $estudiante = Estudiante::findOrFail($id);

            // Crear nuevo historial solo si no existe para el año actual
            $anio = $validated['anio'];
            $historial = HistorialEstudiante::where('id_estudiante', $estudiante->id_estudiante)
                ->where('anio', $anio)
                ->first();

            if (!$historial) {
                HistorialEstudiante::create([
                    'id_estudiante' => $estudiante->id_estudiante,
                    'id_grado'      => $validated['id_grado'],
                    'anio'          => $anio,
                    'estado'        => 'CURSANDO',
                ]);
            }

            $relacion = ResponsableEstudiante::where('id_estudiante', $estudiante->id_estudiante)->first();

            if ($relacion) {
                $relacion->update([
                    'id_responsable' => $validated['id_responsable'],
                    'parentesco'     => $validated['parentesco'],
                    'estado'         => 'ACTIVO',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Historial creado (si no existía) y responsable actualizado correctamente.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error en el proceso',
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
        //
    }
}
