<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HistorialEstudiante;

class permisos extends Model
{
    use HasFactory;
    protected $table = 'Permisos';
    protected $fillable = ['id_permiso', 'id_historial', 'fecha_inicio','fecha_final', 'motivo'];
    protected $primaryKey = 'id_permiso';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function historialestudiante()
    {
        return $this->belongsTo(HistorialEstudiante::class, 'id_historial');
    }
}
