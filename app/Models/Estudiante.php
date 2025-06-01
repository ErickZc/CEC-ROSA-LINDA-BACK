<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;
use App\Models\Seccion;
use App\Models\Nota;

class Estudiante extends Model
{
    use HasFactory;
    protected $table = 'Estudiante';
    protected $fillable = ['id_estudiante', 'id_persona', 'correo', 'estado','nie'];
    protected $primaryKey = 'id_estudiante';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
    public function seccion()
    {
        return $this->belongsTo(seccion::class, 'id_seccion');
    }
    public function notas()
    {
        return $this->hasMany(Nota::class, 'id_estudiante', 'id_estudiante');
    }
}
