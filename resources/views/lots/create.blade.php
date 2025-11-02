<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Registrar Nuevo Lote y Plan de Pago
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('lots.store') }}">
                    @csrf
                    <div class="p-6 md:p-8 space-y-6">
                        
                        <h3 class="text-lg font-medium text-gray-900">
                            Datos del Lote y Asignación
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t pt-6">
                            <div>
                                <x-input-label for="owner_id" value="Socio Propietario" />
                                <select id="owner_id" name="owner_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="">Seleccionar un socio</option>
                                    @foreach($owners as $owner)
                                        <option value="{{ $owner->id }}" @selected(old('owner_id') == $owner->id)>{{ $owner->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('owner_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="client_id" value="Asignar a Cliente (Opcional)" />
                                <select id="client_id" name="client_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">-- Sin asignar --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                             <div>
                                <x-input-label for="block_number" value="Número de Manzana" />
                                <x-text-input id="block_number" name="block_number" class="mt-1 block w-full" :value="old('block_number')" required placeholder="Ej: 10" />
                                <x-input-error :messages="$errors->get('block_number')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="lot_number" value="Número de Lote" />
                                <x-text-input id="lot_number" name="lot_number" class="mt-1 block w-full" :value="old('lot_number')" required placeholder="Ej: 05" />
                                <x-input-error :messages="$errors->get('lot_number')" class="mt-2" />
                            </div>
                        </div>

                        <div class="pt-6 border-t">
                            <h3 class="text-lg font-medium text-gray-900">
                                Plan de Pago Inicial
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 border-t pt-6">
                            <div>
                                <x-input-label for="service_id" value="Servicio" />
                                <select name="service_id" id="service_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    @foreach(\App\Models\Service::all() as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('service_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="total_amount" value="Monto Total" />
                                <x-text-input id="total_amount" name="total_amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('total_amount')" required />
                                <x-input-error :messages="$errors->get('total_amount')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="number_of_installments" value="No. de Cuotas" />
                                <x-text-input id="number_of_installments" name="number_of_installments" type="number" class="mt-1 block w-full" :value="old('number_of_installments')" required />
                                <x-input-error :messages="$errors->get('number_of_installments')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="start_date" value="Fecha de Inicio" />
                                <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', date('Y-m-d'))" required />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 bg-gray-50 px-6 py-4 border-t">
                        <a href="{{ route('lots.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-xs font-semibold uppercase rounded-md hover:bg-gray-500">
                            Cancelar
                        </a>
                        <x-primary-button>
                            Crear Lote y Plan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>