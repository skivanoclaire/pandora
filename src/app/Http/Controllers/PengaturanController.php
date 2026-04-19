<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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

    public function auditTrail(Request $request)
    {
        $query = DB::table('audit_trail_pandora as a')
            ->join('users as u', 'a.user_id', '=', 'u.id')
            ->select(['a.*', 'u.name as user_name', 'u.role as user_role'])
            ->orderByDesc('a.created_at');

        if ($request->get('aksi')) {
            $query->where('a.aksi', $request->get('aksi'));
        }
        if ($request->get('user')) {
            $query->where('a.user_id', $request->get('user'));
        }

        $logs = $query->paginate(50);
        $aksiList = DB::table('audit_trail_pandora')->distinct()->pluck('aksi')->sort();
        $users = DB::table('users')->orderBy('name')->get(['id', 'name']);

        return view('pengaturan.audit-trail', compact('logs', 'aksiList', 'users'));
    }
}
