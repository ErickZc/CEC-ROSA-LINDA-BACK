<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seccion;
use App\Models\HistorialEstudiante;
use App\Models\Estudiante;
use App\Models\Persona;

class Grado extends Model
{
    use HasFactory;
    protected $table = 'Grado';
    protected $primaryKey = 'id_grado';
    protected $fillable = ['id_grado', 'grado', 'id_seccion', 'cantidad_alumnos', 'estado'];

    public $timestamps = false;

    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'id_seccion');
    }

    public function historiales()
    {
        return $this->hasMany(HistorialEstudiante::class, 'id_grado');
    }

    // En HistorialEstudiante.php
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }

    // En Estudiante.php
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

}
