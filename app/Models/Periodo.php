<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    use HasFactory;
    protected $table = 'Periodo';
    protected $primaryKey = 'id_periodo';
    protected $fillable = ['id_periodo', 'periodo','estado'];
}
