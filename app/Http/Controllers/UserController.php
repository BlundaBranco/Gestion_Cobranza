<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        abort_if(! auth()->user()->isAdmin(), 403);

        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        abort_if(! auth()->user()->isAdmin(), 403);

        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:admin,user',
        ]);

        User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function destroy(User $user)
    {
        abort_if(! auth()->user()->isAdmin(), 403);
        abort_if($user->id === auth()->id(), 403);

        $user->delete();

        return back()->with('success', "Usuario {$user->name} eliminado.");
    }

    public function updatePassword(Request $request, User $user)
    {
        abort_if(! auth()->user()->isAdmin(), 403);

        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', "Contraseña de {$user->name} actualizada correctamente.");
    }
}
