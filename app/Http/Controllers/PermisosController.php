<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permisos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermisosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Permisos::all();
    }

    public function getPermisosByResponsable(Request $request)
    {
        $responsable = $request->input('responsable');

        $permisos = Permisos::with([
            'historialestudiante.estudiante.persona',
            'historialestudiante.estudiante.responsableEstudiantes.responsable.persona',
            'historialestudiante.grado'
        ])
            ->when($responsable, function ($query) use ($responsable) {
                $query->whereHas('historialestudiante.estudiante.responsableEstudiantes.responsable.persona', function ($q) use ($responsable) {
                    $q->where('id_persona', $responsable);
                });
            })
            ->paginate(10);

        return response()->json($permisos);
    }

    public function getPermisosByDocente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grados' => 'required|array|min:1',
            'grados.*' => 'integer|exists:Grado,id_grado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Se debe especificar un grado válido',
            ], 400);
        }
        $nombre = $request->input('nombre', '');
        $responsable = $request->input('responsable', '');
        $grados = $request->input('grados');

        $permisos = Permisos::with([
            'historialestudiante.estudiante.persona',
            'historialestudiante.estudiante.responsableEstudiantes.responsable.persona',
            'historialestudiante.grado'
        ])
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%");
                });
            })
            ->when($responsable, function ($query) use ($responsable) {
                $query->whereHas('historialestudiante.estudiante.responsableEstudiantes.responsable.persona', function ($q) use ($responsable) {
                    $q->where('nombre', 'like', "%{$responsable}%");
                });
            })
            ->when(!empty($grados), function ($query) use ($grados) {
                $query->whereHas('historialestudiante.grado', function ($q) use ($grados) {
                    $q->whereIn('id_grado', $grados);
                });
            })
            ->paginate(10);

        return response()->json($permisos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $permisoExistente = Permisos::where('id_historial', $request->input('id_historial'))
            ->whereDate('fecha_inicio', $request->input('fecha_inicio'))
            ->first();

        if ($permisoExistente) {
            return response()->json([
                'message' => 'Ya existe un permiso para esta fecha de inicio.',
                'conflict' => true,
                'permiso_existente' => $permisoExistente
            ], 409); // 409 Conflict
        }

        DB::beginTransaction();

        try {
            $permiso = Permisos::create([
                'id_historial' => $request->input('id_historial'),
                'fecha_inicio' => $request->input('fecha_inicio'),
                'fecha_final' => $request->input('fecha_final'),
                'motivo' => $request->input('motivo'),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Permiso creado correctamente'
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
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
