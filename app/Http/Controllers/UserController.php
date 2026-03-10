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
