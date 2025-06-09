<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Ciclo;

class CicloController extends Controller
{
    public function allCiclos(){
        return Ciclo::all();
    }

    public function index()
    {
       //nada al momento
    }
}
