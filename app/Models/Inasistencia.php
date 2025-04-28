<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inasistencia extends Model
{
    use HasFactory;
    protected $table = 'Inasistencia';
    protected $fillable = ['id_inasistencia', 'id_historial', 'fecha', 'motivo', 'estado'];
}
