<?php

namespace App\Http\Controllers;

use App\Models\Banca;
use Illuminate\Http\Request;

class BancaController extends Controller
{
    public function index()
    {
        return view('admin.bancas.index');
    }

    public function create()
    {
        return view('admin.bancas.create');
    }

    public function edit($id)
    {
        return view('admin.bancas.edit', compact('id'));
    }

    public function movimientos($id)
    {
        return view('admin.bancas.movimientos', compact('id'));
    }

    public function cargar($id)
    {
        return view('admin.bancas.cargar', compact('id'));
    }
}
