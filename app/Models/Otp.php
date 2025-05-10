<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

class Otp extends Model
{
    use HasFactory;
    protected $table = 'OTP';
    protected $primaryKey = 'id_otp';
    protected $fillable = ['id_otp', 'codigo','fecha_hora','id_usuario'];

    // Deshabilitar los timestamps
    public $timestamps = false;

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

}
