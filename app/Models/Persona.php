<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;
    protected $table = 'Persona';
    protected $fillable = ['id_persona', 'nombre', 'apellido', 'direccion', 'telefono', 'genero', 'fecha_creacion'];
    protected $primaryKey = 'id_persona';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'id_persona');
    }

    public function docente()
    {
        return $this->hasOne(Docente::class, 'id_persona');
    }
}
