<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Gestionar Lote: {{ $lot->identifier }}</h2>
                <p class="text-sm text-gray-600">Administra información y planes de pago</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Formulario de Edición del Lote -->
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200">
                <form method="POST" action="{{ route('lots.update', $lot) }}">
                    @csrf
                    @method('PUT')
                    <div class="p-6 md:p-8 space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Información del Lote</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t-2 border-gray-100 pt-6">
                            <div>
                                <x-input-label for="identifier" value="Identificador" />
                                <x-text-input id="identifier" name="identifier" class="mt-1 block w-full" :value="old('identifier', $lot->identifier)" required />
                            </div>
                            <div>
                                <x-input-label for="status" value="Estado" />
                                <select id="status" name="status" class="mt-1 block w-full border-2 border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150">
                                    <option value="disponible" @selected(old('status', $lot->status) == 'disponible')>Disponible</option>
                                    <option value="vendido" @selected(old('status', $lot->status) == 'vendido')>Vendido</option>
                                    <option value="liquidado" @selected(old('status', $lot->status) == 'liquidado')>Liquidado</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label value="Cliente Asignado" />
                                <div class="mt-1 flex items-center gap-3 px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold text-sm">
                                            {{ $lot->client ? substr($lot->client->name, 0, 2) : 'SA' }}
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900">{{ $lot->client->name ?? 'Sin asignar' }}</p>
                                    </div>
                                </div>
                                <p class="mt-2 text-sm text-gray-600 flex items-start gap-2">
                                    <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Para cambiar el propietario, utiliza la sección "Transferir Lote" más abajo.</span>
                                </p>
                                <input type="hidden" name="client_id" value="{{ $lot->client_id }}">
                                <input type="hidden" name="total_price" value="{{ $lot->total_price }}">
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-4 bg-gradient-to-br from-gray-50 to-blue-50 px-6 py-4 border-t-2 border-gray-100">
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Cambios
                        </x-primary-button>
                    </div>
                </form>
            </div>

            <!-- Planes de Pago -->
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6 md:p-8">
                @include('lots._payment-plans-section')
            </div>

            <!-- Transferencia de Lote -->
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6 md:p-8">
                @include('lots._transfer-section')
            </div>

            <!-- Historial de Propietarios -->
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 p-6 md:p-8">
                @include('lots._history-section')
            </div>
        </div>
    </div>
</x-app-layout>