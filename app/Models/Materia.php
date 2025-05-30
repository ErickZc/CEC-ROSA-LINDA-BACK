<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use HasFactory;
    protected $table = 'Materia';
    protected $primaryKey = 'id_materia';
    protected $fillable = ['id_materia', 'nombre_materia', 'estado'];
    
    public $timestamps = false;
}
