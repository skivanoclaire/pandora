<?php

namespace App\Http\Controllers;

class AnalitikController extends Controller
{
    public function tren()
    {
        return view('analitik.tren');
    }

    public function anomali()
    {
        return view('analitik.anomali');
    }

    public function clustering()
    {
        return view('analitik.clustering');
    }
}
