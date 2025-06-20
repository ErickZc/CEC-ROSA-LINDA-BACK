<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Docente;


class DocenteMateriaGrado extends Model
{
    use HasFactory;
    protected $table = 'Docente_Materia_Grado';
    protected $fillable = ['id_docente_materia', 'id_docente', 'id_materia', 'id_grado', 'fecha_asginacion', 'estado'];

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'id_grado');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente');
    }
}
