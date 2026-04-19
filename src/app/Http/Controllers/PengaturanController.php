<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
    public function index()
    {
        $userCount = User::count();
        $auditCount = DB::table('audit_trail_pandora')->count();
        $lastAudit = DB::table('audit_trail_pandora')->orderByDesc('created_at')->first();
        return view('pengaturan.index', compact('userCount', 'auditCount', 'lastAudit'));
    }
}
