<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestionar Lote: {{ $lot->identifier }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Formulario de Edición del Lote -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('lots.update', $lot) }}">
                    @csrf
                    @method('PUT')
                    <div class="p-6 md:p-8 space-y-6">
                        <h3 class="text-lg font-medium text-gray-900">Información del Lote</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t pt-6">
                            
                            <!-- Socio Propietario -->
                            <div>
                                <x-input-label for="owner_id" value="Socio Propietario" />
                                @if($lot->owner)
                                    <p class="mt-1 block w-full rounded-md shadow-sm px-3 py-2 bg-gray-100 border-gray-300 text-gray-500">
                                        {{ $lot->owner->name }}
                                    </p>
                                @else
                                    <select id="owner_id" name="owner_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="">Asignar un socio</option>
                                        @foreach(\App\Models\Owner::orderBy('name')->get() as $owner)
                                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-sm text-yellow-600">Este lote no tiene socio. Por favor, asígnele uno.</p>
                                    <x-input-error :messages="$errors->get('owner_id')" class="mt-2" />
                                @endif
                            </div>

                            <!-- Cliente Asignado -->
                            <div>
                                <x-input-label value="Cliente Asignado" />
                                <p class="mt-1 block w-full rounded-md shadow-sm px-3 py-2 bg-gray-100 border-gray-300 text-gray-500">
                                    {{ $lot->client->name ?? 'Sin asignar' }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500">Usa la sección "Transferir Lote" para cambiarlo.</p>
                            </div>

                            <!-- Identifier y Estado -->
                            <div>
                                <x-input-label for="identifier" value="Identificador" />
                                <x-text-input id="identifier" name="identifier" class="mt-1 block w-full" :value="old('identifier', $lot->identifier)" required />
                                <x-input-error :messages="$errors->get('identifier')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="status" value="Estado" />
                                <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="disponible" @selected(old('status', $lot->status) == 'disponible')>Disponible</option>
                                    <option value="vendido" @selected(old('status', $lot->status) == 'vendido')>Vendido</option>
                                    <option value="liquidado" @selected(old('status', $lot->status) == 'liquidado')>Liquidado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-4 bg-gray-50 px-6 py-4 border-t">
                        <a href="{{ route('lots.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-xs font-semibold uppercase rounded-md hover:bg-gray-500">Cancelar</a>
                        <x-primary-button>Guardar Cambios</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Planes de Pago -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 md:p-8">
                @include('lots._payment-plans-section')
            </div>

            <!-- Transferencia de Lote -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 md:p-8">
                @include('lots._transfer-section')
            </div>

            <!-- Historial de Propietarios -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 md:p-8">
                @include('lots._history-section')
            </div>
        </div>
    </div>
</x-app-layout>