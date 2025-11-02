<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Crear Nuevo Socio
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @include('owners._form', ['owner' => new \App\Models\Owner(), 'action' => route('owners.store'), 'method' => 'POST', 'buttonText' => 'Guardar Socio'])
            </div>
        </div>
    </div>
</x-app-layout>