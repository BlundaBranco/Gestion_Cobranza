<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Crear Nuevo Servicio</h2>
                <p class="text-sm text-gray-600">Registra un nuevo concepto de cobro</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-md border-2 border-gray-200 overflow-hidden">
                <form method="POST" action="{{ route('services.store') }}">
                    @csrf
                    <div class="p-6 md:p-8 space-y-6">
                        
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Información del Servicio</h3>
                                <p class="text-sm text-gray-600">Define un nuevo concepto de cobro (ej. 'Terreno', 'Electrificación').</p>
                            </div>
                        </div>

                        <div class="space-y-6 border-t-2 border-gray-100 pt-6">
                            <!-- Name -->
                            <div>
                                <x-input-label for="name" value="Nombre del Servicio" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus placeholder="Ej: Terreno Principal" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Description -->
                            <div>
                                <x-input-label for="description" value="Descripción (Opcional)" />
                                <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" placeholder="Detalles adicionales sobre el servicio">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 bg-gradient-to-br from-gray-50 to-blue-50 px-6 py-4 border-t-2 border-gray-100">
                        <a href="{{ route('services.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-gray-300 rounded-xl font-semibold text-sm text-gray-700 hover:bg-gray-50 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </a>
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Servicio
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>