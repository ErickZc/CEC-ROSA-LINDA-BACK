<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HistorialEstudiante;

class Inasistencia extends Model
{
    use HasFactory;
    protected $table = 'Inasistencia';
    protected $fillable = ['id_inasistencia', 'id_historial', 'fecha', 'motivo', 'estado'];
    protected $primaryKey = 'id_inasistencia';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function historialestudiante()
    {
        return $this->belongsTo(HistorialEstudiante::class, 'id_historial');
    }
}
