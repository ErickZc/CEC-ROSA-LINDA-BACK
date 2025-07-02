<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Nota;
use App\Models\Inasistencia;

class HistorialEstudiante extends Model
{
    use HasFactory;
    protected $table = 'Historial_Estudiante';
    protected $fillable = ['id_historial', 'id_estudiante', 'id_grado', 'anio', 'estado'];
    protected $primaryKey = 'id_historial';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'id_grado');
    }
    public function notas()
    {
        return $this->hasMany(Nota::class, 'id_historial', 'id_historial');
    }

    public function inasistencias()
    {
        return $this->hasMany(Inasistencia::class, 'id_historial');
    }
}
