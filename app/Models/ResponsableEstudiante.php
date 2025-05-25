<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsableEstudiante extends Model
{
    use HasFactory;
    protected $table = 'Responsable_Estudiante';
    protected $primaryKey = 'id_responsable_estudiante';
    protected $fillable = ['id_responsable_estudiante','id_responsable', 'id_estudiante', 'parentesco', 'estado'];

    public $timestamps = false;

    public function responsable()
    {
        return $this->belongsTo(Responsable::class, 'id_responsable');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }
}
