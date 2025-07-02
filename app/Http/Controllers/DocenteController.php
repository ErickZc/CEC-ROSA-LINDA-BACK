<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DocenteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function allDocentes()
    // {
    //     return Docente::all();
    // }
    
    public function allDocentes()
    {
        return Docente::with('persona')->get();
    }


    public function index(Request $request)
    {
        $search = $request->input('search', '');

        $docentes = Docente::with([
            'persona.usuario.rol'
        ])
        ->where('estado', 'ACTIVO')
        ->whereHas('persona', function ($query) use ($search) {
            $query->where('nombre', 'like', "%{$search}%")
                ->orWhere('dui', 'like', "%{$search}%")
                ->orWhereHas('usuario', function ($q) use ($search) {
                    $q->where('usuario', 'like', "%{$search}%")
                    ->orWhere('correo', 'like', "%{$search}%");
                });
        })
        ->paginate(10);

        return response()->json($docentes);
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
        
        // Usuario general
        $rules['usuario'] = 'required|string|unique:Usuario,usuario';
        $rules['correo'] = 'required|email|unique:Usuario,correo';
        $rules['password'] = 'required|string|min:8';

        // Docente
        $rules['dui'] = 'required|string|size:10|unique:Docente,dui';
        $rules['nit'] = 'required|string|size:17|unique:Docente,nit';


        $validated = Validator::make($request->all(), $rules)->validate();
        $validated['id_rol'] = 2;

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

            Docente::create([
                'id_persona' => $persona->id_persona,
                'dui'        => $validated['dui'],
                'nit'        => $validated['nit'],
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
        $validated = $request->validate([
            'nombre'    => 'required|string|max:50',
            'apellido'  => 'required|string|max:50',
            'direccion' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:15',
            'genero'    => 'required|in:MASCULINO,FEMENINO,OTRO',
            
            'password'  => 'nullable|string|min:8',

            'dui'       => 'required|string|size:10|unique:Docente,dui,' . $id . ',id_docente',
            'nit'       => 'required|string|size:17|unique:Docente,nit,' . $id . ',id_docente',
        ]);

        // Obtener al docente y su relación con persona
        $docente = Docente::findOrFail($id);
        $persona = $docente->persona;

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

        // Actualizar docente
        $docente->dui = $validated['dui'];
        $docente->nit = $validated['nit'];
        $docente->save();

        return response()->json([
            'message' => 'Docente actualizado correctamente',
            'data' => [
                'usuario' => $usuario->load('persona', 'rol'),
                'docente' => $docente
            ]
        ]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $docente = Docente::find($id);

        if (!$docente) {
            return response()->json(['message' => 'Docente no encontrado'], 404);
        }

        $docente->estado = 'INACTIVO';
        $docente->save();

        $usuario = Usuario::where('id_persona', $docente->id_persona)->first();
        if ($usuario && $usuario->estado !== 'INACTIVO') {
            $usuario->estado = 'INACTIVO';
            $usuario->save();
        }

        return response()->json(['message' => 'Docente eliminado correctamente']);
    }
}
