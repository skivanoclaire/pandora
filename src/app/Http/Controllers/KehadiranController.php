<?php

namespace App\Http\Controllers;

class KehadiranController extends Controller
{
    public function rekap()
    {
        return view('kehadiran.rekap');
    }

    public function log()
    {
        return view('kehadiran.log');
    }
}
