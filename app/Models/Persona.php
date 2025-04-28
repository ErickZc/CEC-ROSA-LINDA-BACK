<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;
    protected $table = 'Persona';
    protected $fillable = ['id_persona', 'nombre', 'apellido', 'direccion', 'telefono', 'genero', 'fecha_creacion'];
}
