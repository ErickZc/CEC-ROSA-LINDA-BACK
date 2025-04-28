<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocenteMateriaGrado extends Model
{
    use HasFactory;
    protected $table = 'Docente_Materia_Grado';
    protected $fillable = ['id_docente_materia', 'id_docente', 'id_materia', 'id_grado', 'fecha_asginacion', 'estado'];
}
