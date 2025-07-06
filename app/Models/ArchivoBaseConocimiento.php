<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivoBaseConocimiento extends Model
{
    use HasFactory;
    protected $table = 'Archivos_Base_Conocimiento';
    protected $primaryKey = 'id';
 
    public $timestamps = false;
}
