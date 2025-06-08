<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
 
class Ciclo extends Model
{
     use HasFactory;
    protected $table = 'Ciclo';
    protected $primaryKey = 'id_ciclo';
    protected $fillable = ['id_ciclo', 'ciclo'];
 
    public $timestamps = false;
}