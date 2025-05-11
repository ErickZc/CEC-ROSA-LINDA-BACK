<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;

class Estudiante extends Model
{
    use HasFactory;
    protected $table = 'Estudiante';
    protected $fillable = ['id_estudiante', 'id_persona', 'correo', 'estado'];
    protected $primaryKey = 'id_estudiante';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
}
