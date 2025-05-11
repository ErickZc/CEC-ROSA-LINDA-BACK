<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    use HasFactory;
    protected $table = 'Seccion';
    protected $primaryKey = 'id_seccion';
    protected $fillable = ['id_seccion', 'seccion', 'estado'];

    public $timestamps = false;
}
