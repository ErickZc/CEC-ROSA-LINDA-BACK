<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;

class Docente extends Model
{
    use HasFactory;
    protected $table = 'Docente';
    protected $primaryKey = 'id_docente';
    protected $fillable = ['id_docente', 'id_persona', 'dui', 'nit', 'estado'];

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'id_persona', 'id_persona');
    }
}
