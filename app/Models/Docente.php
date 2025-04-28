<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use HasFactory;
    protected $table = 'Docente';
    protected $fillable = ['id_Docente', 'id_Persona', 'dui', 'nit', 'estado'];
}
