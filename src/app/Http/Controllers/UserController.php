<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Integrity\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(30);
        return view('pengaturan.users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:18',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,hr,pimpinan',
        ]);

        $user = User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        AuditTrail::catat(auth()->id(), 'tambah_user', 'users', $user->id, ['name' => $user->name, 'role' => $user->role]);

        return back()->with('success', "User {$user->name} berhasil ditambahkan.");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:18',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,hr,pimpinan',
        ]);

        $user->update([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'role' => $request->role,
        ] + ($request->filled('password') ? ['password' => Hash::make($request->password)] : []));

        AuditTrail::catat(auth()->id(), 'ubah_user', 'users', $user->id, ['name' => $user->name, 'role' => $user->role]);

        return back()->with('success', "User {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Tidak bisa menghapus akun sendiri.');

        $name = $user->name;
        AuditTrail::catat(auth()->id(), 'hapus_user', 'users', $user->id, ['name' => $name]);
        $user->delete();

        return back()->with('success', "User {$name} berhasil dihapus.");
    }
}
