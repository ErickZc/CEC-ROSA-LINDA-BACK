<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Persona;
use App\Models\RolUsuario;

class Usuario extends Model
{
    use HasFactory;
    protected $table = 'Usuario';
    protected $primaryKey = 'id_usuario';
    protected $fillable = ['id_usuario', 'usuario','password','correo','id_rol','id_persona','estado'];

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function rol()
    {
        return $this->belongsTo(RolUsuario::class, 'id_rol');
    }


}

