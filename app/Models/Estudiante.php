<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;
use App\Models\ResponsableEstudiante;
use App\Models\HistorialEstudiante;
use App\Models\Seccion;
use App\Models\Nota;

class Estudiante extends Model
{
    use HasFactory;
    protected $table = 'Estudiante';
    protected $fillable = ['id_estudiante', 'id_persona', 'correo', 'estado', 'nie'];
    protected $primaryKey = 'id_estudiante';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function responsableEstudiantes()
    {
        return $this->hasMany(ResponsableEstudiante::class, 'id_estudiante');
    }

    public function historialEstudianteActual()
    {
        $anioActual = date('Y');

        return $this->hasOne(HistorialEstudiante::class, 'id_estudiante', 'id_estudiante')
            ->where('anio', $anioActual)
            ->where('estado', 'CURSANDO');
    }
    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'id_seccion');
    }
    /*public function notas()
    {
        return $this->hasMany(Nota::class, 'id_estudiante', 'id_estudiante');
    }*/

     public function historiales()
    {
        return $this->hasMany(HistorialEstudiante::class, 'id_estudiante', 'id_estudiante');
    }

    public function notas()
    {
        return $this->hasManyThrough(
            Nota::class,
            HistorialEstudiante::class,
            'id_estudiante',  // FK en HistorialEstudiante que referencia a Estudiante
            'id_historial',   // FK en Nota que referencia a HistorialEstudiante
            'id_estudiante',  // PK en Estudiante
            'id_historial'    // PK en HistorialEstudiante
        );
    }
}
