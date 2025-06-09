<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ciclo;

class Materia extends Model
{
    use HasFactory;
    protected $table = 'Materia';
    protected $primaryKey = 'id_materia';
    protected $fillable = ['id_materia', 'nombre_materia', 'estado','id_ciclo'];
    
    public $timestamps = false;

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'id_ciclo');
    }
}
