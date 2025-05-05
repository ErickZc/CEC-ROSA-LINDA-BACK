<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolUsuario extends Model
{
    use HasFactory;
    protected $table = 'Rol_Usuario';
    protected $fillable = ['id_rol', 'rol'];
    protected $primaryKey = 'id_rol';
}
