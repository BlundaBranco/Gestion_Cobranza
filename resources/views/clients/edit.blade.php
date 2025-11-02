<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 leading-tight">
            Editar Cliente: {{ $client->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Form Card --}}
            <div class="bg-white border-2 border-gray-200 overflow-hidden shadow-md sm:rounded-xl">
                <form method="POST" action="{{ route('clients.update', $client) }}">
                    @csrf
                    @method('PUT')
                    <div class="p-6 md:p-8 space-y-6">
                        
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Información del Cliente
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Actualiza los datos del cliente.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t-2 border-gray-100 pt-6">
                            <!-- Name -->
                            <div class="md:col-span-2">
                                <x-input-label for="name" value="Nombre Completo" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $client->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <x-input-label for="email" value="Email" />
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <x-text-input id="email" name="email" type="email" class="block w-full pl-10" :value="old('email', $client->email)" />
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <!-- Phone -->
                            <div>
                                <x-input-label for="phone" value="Teléfono" />
                                <div class="relative mt-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <x-text-input id="phone" name="phone" type="tel" class="block w-full pl-10" :value="old('phone', $client->phone)" />
                                </div>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <x-input-label for="address" value="Dirección" />
                                <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">{{ old('address', $client->address) }}</textarea>
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-4 border-t-2 border-gray-200">
                        <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </a>
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Cambios
                        </x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Sección de Documentos --}}
            <div class="bg-white border-2 border-gray-200 overflow-hidden shadow-md sm:rounded-xl">
                <div class="p-6 md:p-8">
                    <div class="flex items-start space-x-4 mb-6">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Documentos del Cliente
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Adjunta enlaces a documentos importantes (ej. contratos en OneDrive).
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('clients.documents.store', $client) }}" class="mb-6 border-t-2 border-gray-100 pt-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div class="md:col-span-1">
                                <x-input-label for="document_name" value="Nombre del Documento" />
                                <x-text-input id="document_name" name="document_name" class="mt-1 block w-full" required placeholder="Ej: Contrato firmado" />
                                <x-input-error :messages="$errors->get('document_name')" class="mt-2" />
                            </div>
                            <div class="md:col-span-1">
                                <x-input-label for="document_url" value="URL del Documento" />
                                <x-text-input id="document_url" name="document_url" type="url" class="mt-1 block w-full" required placeholder="https://..." />
                                <x-input-error :messages="$errors->get('document_url')" class="mt-2" />
                            </div>
                            <div>
                                <x-primary-button class="w-full justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Agregar
                                </x-primary-button>
                            </div>
                        </div>
                    </form>

                    <div class="border-t-2 border-gray-100 pt-6">
                        <ul class="space-y-3">
                            @forelse($client->documents as $document)
                                <li class="flex justify-between items-center p-4 bg-gradient-to-r from-gray-50 to-white rounded-lg border-2 border-gray-200 hover:border-purple-300 hover:shadow-sm transition-all duration-200">
                                    <a href="{{ $document->document_url }}" target="_blank" class="flex items-center font-medium text-purple-600 hover:text-purple-700 group">
                                        <svg class="w-5 h-5 mr-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="group-hover:underline">{{ $document->document_name }}</span>
                                        <svg class="w-4 h-4 ml-1 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este documento?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 font-medium text-xs rounded-lg transition-colors duration-150">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </li>
                            @empty
                                <li class="text-center py-12">
                                    <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 font-medium">No hay documentos para este cliente</p>
                                    <p class="text-sm text-gray-400 mt-1">Agrega el primer documento usando el formulario arriba</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>