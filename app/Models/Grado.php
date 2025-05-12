<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Seccion;

class Grado extends Model
{
    use HasFactory;
    protected $table = 'Grado';
    protected $fillable = ['id_grado', 'grado', 'id_seccion', 'cantidad_alumnos', 'estado'];
    protected $primaryKey = 'id_grado';

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function seccion()
    {
        return $this->belongsTo(Seccion::class, 'id_seccion');
    }
}
