<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;
    protected $table = 'Nota';
    protected $fillable = ['id_nota', 'id_historial','id_materia', 'actividad1', 'actividad2', 'actividad3', 'actividadInt', 'examen', 'promedio', 'id_periodo'];
}
