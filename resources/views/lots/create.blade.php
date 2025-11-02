<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Registrar Nuevo Lote y Plan de Pago</h2>
                <p class="text-sm text-gray-600">Crea un lote y su plan de pago inicial</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 overflow-hidden">
                <form method="POST" action="{{ route('lots.store') }}">
                    @csrf
                    <div class="p-6 md:p-8 space-y-6">
                        
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Datos del Lote y Asignación</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t-2 border-gray-100 pt-6">
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
                            <div class="md:col-span-2">
                                <x-input-label for="client_id" value="Asignar a Cliente (Opcional)" />
                                <select id="client_id" name="client_id" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150">
                                    <option value="">-- Sin asignar --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="pt-6 border-t-2 border-gray-100">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Plan de Pago Inicial</h3>
                                    <p class="text-sm text-gray-600">Se creará un plan de pago inicial para este lote.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 border-t-2 border-gray-100 pt-6">
                            <div>
                                <x-input-label for="service_id" value="Servicio" />
                                <select name="service_id" id="service_id" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" required>
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

                    <div class="flex items-center justify-end gap-4 bg-gradient-to-br from-gray-50 to-blue-50 px-6 py-4 border-t-2 border-gray-100">
                        <a href="{{ route('lots.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-300 rounded-xl font-semibold text-sm text-gray-700 hover:bg-gray-50 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </a>
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Crear Lote y Plan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>