<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function index()
    {
        $owners = Owner::latest()->paginate(10);
        return view('owners.index', compact('owners'));
    }

    public function create()
    {
        return view('owners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:owners',
            'contact_info' => 'nullable|string',
        ]);

        Owner::create($validated);
        return redirect()->route('owners.index')->with('success', 'Socio creado exitosamente.');
    }

    public function edit(Owner $owner)
    {
        return view('owners.edit', compact('owner'));
    }

    public function update(Request $request, Owner $owner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:owners,name,' . $owner->id,
            'contact_info' => 'nullable|string',
        ]);

        $owner->update($validated);
        return redirect()->route('owners.index')->with('success', 'Socio actualizado exitosamente.');
    }

    public function destroy(Owner $owner)
    {
        $owner->loadCount('lots');

        if ($owner->lots_count > 0) {
            return back()->with('error', 'No se puede eliminar el socio porque tiene lotes asociados.');
        }

        $owner->delete();
        return redirect()->route('owners.index')->with('success', 'Socio eliminado exitosamente.');
    }
}