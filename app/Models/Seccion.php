<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Periodo;

class Seccion extends Model
{
    use HasFactory;
    protected $table = 'Seccion';
    protected $primaryKey = 'id_seccion';
    protected $fillable = ['id_seccion', 'seccion', 'estado'];

    public $timestamps = false;

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'id_periodo');
    }
}
