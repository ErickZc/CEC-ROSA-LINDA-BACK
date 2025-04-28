<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialEstudiante extends Model
{
    use HasFactory;
    protected $table = 'Historial_Estudiante';
    protected $fillable = ['id_historial', 'id_estudiante', 'id_grado', 'anio', 'estado'];
}
