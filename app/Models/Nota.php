<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Materia;
use App\Models\Periodo;

class Nota extends Model
{
    use HasFactory;
    protected $table = 'Nota';
    protected $primaryKey = 'id_nota';
    protected $fillable = ['id_nota', 'id_historial','id_materia', 'actividad1', 'actividad2', 'actividad3', 'actividadInt', 'examen', 'promedio', 'id_periodo'];
   
    // Deshabilitar los timestamps
    public $timestamps = false;


    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia');
    }
    
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'id_periodo', 'id_periodo');
    }
}
