<?php

namespace App\Http\Controllers;

class MasterController extends Controller
{
    public function instansi()
    {
        return view('master.instansi');
    }

    public function pegawai()
    {
        return view('master.pegawai');
    }

    public function geofence()
    {
        return view('master.geofence');
    }
}
