<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestionar Lote: {{ $lot->identifier }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Formulario de Edición del Lote -->
            <div class="bg-white shadow-md sm:rounded-lg overflow-hidden border border-gray-100">
                <form method="POST" action="{{ route('lots.update', $lot) }}">
                    @csrf
                    @method('PUT')
                    <div class="p-6 md:p-8 space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                            <h3 class="text-lg font-semibold text-gray-900">Información del Lote</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 border-t border-gray-200 pt-6">
                            <div>
                                <x-input-label for="owner_id" value="Socio Propietario" />
                                @if($lot->owner)
                                    <p class="mt-1 block w-full rounded-md shadow-sm px-3 py-2 bg-blue-50 border border-blue-200 text-gray-700">
                                        {{ $lot->owner->name }}
                                    </p>
                                    <input type="hidden" name="owner_id" value="{{ $lot->owner_id }}">
                                @else
                                    <select id="owner_id" name="owner_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <option value="">Asignar un socio</option>
                                        @foreach(\App\Models\Owner::orderBy('name')->get() as $owner)
                                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-sm text-amber-600 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Este lote no tiene socio. Por favor, asígnele uno.
                                    </p>
                                @endif
                            </div>

                            <div>
                                <x-input-label for="identifier" value="Identificador" />
                                <x-text-input id="identifier" name="identifier" class="mt-1 block w-full focus:border-blue-500 focus:ring-blue-500" :value="old('identifier', $lot->identifier)" required />
                            </div>

                            <div>
                                <x-input-label for="status" value="Estado" />
                                <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="disponible" @selected(old('status', $lot->status) == 'disponible')>Disponible</option>
                                    <option value="vendido" @selected(old('status', $lot->status) == 'vendido')>Vendido</option>
                                    <option value="liquidado" @selected(old('status', $lot->status) == 'liquidado')>Liquidado</option>
                                </select>
                            </div>
                            
                            <div class="lg:col-span-3">
                                <x-input-label value="Cliente Asignado" />
                                <p class="mt-1 block w-full rounded-md shadow-sm px-3 py-2 bg-gray-50 border border-gray-200 text-gray-700">
                                    {{ $lot->client->name ?? 'Sin asignar' }}
                                </p>
                                <p class="mt-2 text-sm text-gray-500 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    Para cambiar el propietario, utiliza la sección "Transferir Lote" más abajo.
                                </p>
                            </div>

                            <!-- NUEVO CAMPO DE NOTAS -->
                            <div class="md:col-span-2">
                                <x-input-label for="notes" value="Notas (Observaciones del lote)" />
                                <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $lot->notes) }}</textarea>
                            </div>

                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-4 bg-gray-50 px-6 py-4 border-t border-gray-200">
                        <x-primary-button>Guardar Cambios</x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Planes de Pago -->
            <div class="bg-white shadow-md sm:rounded-lg overflow-hidden border border-gray-100">
                <div class="p-6 md:p-8">
                    @include('lots._payment-plans-section')
                </div>
            </div>

            <!-- Transferencia de Lote -->
            <div class="bg-white shadow-md sm:rounded-lg overflow-hidden border border-gray-100">
                <div class="p-6 md:p-8">
                    @include('lots._transfer-section')
                </div>
            </div>

            <!-- Transferencia de Socio -->
            <div class="bg-white shadow-md sm:rounded-lg overflow-hidden border border-gray-100">
                <div class="p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-1 h-6 bg-indigo-600 rounded-full"></div>
                        <h3 class="text-lg font-semibold text-gray-900">Cambiar Socio Propietario</h3>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">Transfiere la propiedad del lote a otro socio.</p>

                    <form method="POST" action="{{ route('lots.transfer-owner', $lot) }}" class="mt-6 border-t border-gray-200 pt-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                            <div>
                                <x-input-label for="new_owner_id" value="Nuevo Socio" />
                                <select name="new_owner_id" id="new_owner_id" class="mt-1 block w-full border-gray-300 bg-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    @foreach(\App\Models\Owner::where('id', '!=', $lot->owner_id)->orderBy('name')->get() as $owner)
                                        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('new_owner_id')" class="mt-2" />
                            </div>
                            <div class="flex justify-end">
                                <x-primary-button>Cambiar Socio</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historial de Propietarios -->
            <div class="bg-white shadow-md sm:rounded-lg overflow-hidden border border-gray-100">
                <div class="p-6 md:p-8">
                    @include('lots._history-section')
                </div>
            </div>

            <!-- Zona de Peligro -->
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 md:p-8 mt-6 border-l-4 border-red-500">
                <h3 class="text-lg font-medium text-red-400">Zona de Peligro</h3>
                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-gray-100">Eliminar este Lote</p>
                        <p class="text-sm text-gray-400">Esta acción no se puede deshacer. Se eliminará el lote permanentemente.</p>
                    </div>
                    @can('forceDelete', $lot)
                        <form action="{{ route('lots.destroy', $lot) }}" method="POST" onsubmit="return confirm('¿Estás absolutamente seguro? Esta acción es irreversible.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500">
                                Eliminar Lote
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Inicializa todos los selectores con la clase 'select2' en esta página
        $('.select2').select2({
            theme: "classic",
            placeholder: "Selecciona o busca un cliente",
            allowClear: true
        });
    });
</script>
@endpush

</x-app-layout>