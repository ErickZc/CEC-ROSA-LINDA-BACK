<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inasistencia;
use Carbon\Carbon;

class InasistenciaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inasistencia::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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

    public function getInasistenciaReport(Request $request){
        // Obtener el parámetro de búsqueda, si existe
        $grado = $request->input('grado', ''); //obligatorio
        $nombre = $request->input('nombre', '');
        $desde = $request->input('desde', ''); 
        $hasta = $request->input('hasta', '');

        // Filtrar las inasistencias según el parámetro de búsqueda
        $inasistencias = Inasistencia::with(['historialestudiante.estudiante.persona', 'historialestudiante.grado'])
                        ->when($grado, function ($query) use ($grado) {
                            $query->whereHas('historialestudiante.grado', function ($q) use ($grado) {
                                $q->where('id_grado', "{$grado}");
                            });
                        })
                        ->when($nombre, function ($query) use ($nombre) {
                            $query->whereHas('historialestudiante.estudiante.persona', function ($q) use ($nombre) {
                                $q->where('nombre', 'like', "%{$nombre}%");
                            });
                        })
                        ->when($desde && $hasta, function ($query) use ($desde, $hasta) {
                            $desde = Carbon::parse($desde)->startOfDay(); 
                            $hasta = Carbon::parse($hasta)->endOfDay();   
                    
                            $query->whereBetween('fecha', [$desde, $hasta])->get();
                    
                            
                        })
                        ->paginate(10);

        // Devolver los usuarios paginados
        return response()->json($inasistencias);
    }
}
