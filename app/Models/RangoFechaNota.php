<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Periodo;

class RangoFechaNota extends Model
{
    use HasFactory;
    protected $table = 'Rango_Fecha_Nota';
    protected $primaryKey = 'id_rango';
    public $timestamps = false;

    protected $fillable = [
        'id_periodo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'id_periodo', 'id_periodo');
    }
}
