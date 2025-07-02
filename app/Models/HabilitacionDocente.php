<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Docente;
use App\Models\Periodo;

class HabilitacionDocente extends Model
{
    use HasFactory;
    protected $table = 'Habilitacion_Docente';
    protected $primaryKey = 'id_habilitacion';
    public $timestamps = false;

    protected $fillable = [
        'id_docente',
        'id_periodo',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado'
    ];

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_docente');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'id_periodo');
    }


}
