<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->paginate(10);
        return view('services.index', compact('services'));
    }

    public function create()
    {
        return view('services.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services',
            'description' => 'nullable|string',
        ]);

        Service::create($validated);
        return redirect()->route('services.index')->with('success', 'Servicio creado exitosamente.');
    }

    public function edit(Service $service)
    {
        return view('services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:services,name,' . $service->id,
            'description' => 'nullable|string',
        ]);

        $service->update($validated);
        return redirect()->route('services.index')->with('success', 'Servicio actualizado exitosamente.');
    }

    public function destroy(Service $service)
    {
        // Carga de forma eficiente la cuenta de planes de pago asociados.
        $service->loadCount('paymentPlans');

        if ($service->payment_plans_count > 0) {
            return back()->with('error', 'No se puede eliminar el servicio porque está en uso por ' . $service->payment_plans_count . ' plan(es) de pago.');
        }

        $service->delete();
        return redirect()->route('services.index')->with('success', 'Servicio eliminado exitosamente.');
    }
    
}