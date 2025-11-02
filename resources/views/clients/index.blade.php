<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                Gestión de Clientes
            </h2>
            <a href="{{ route('clients.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 mt-4 sm:mt-0 shadow-md hover:shadow-lg">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Cliente
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Search and Filters -->
            <div class="bg-white border-2 border-gray-200 shadow-md sm:rounded-xl p-6">
                <form action="{{ route('clients.index') }}" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-3">
                            <x-input-label for="search" value="Buscar Cliente" class="mb-2" />
                            <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Nombre, email o teléfono..." />
                        </div>
                        <div class="flex items-end gap-2">
                            <x-primary-button class="w-full justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Buscar
                            </x-primary-button>
                            <a href="{{ route('clients.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Clients Table -->
            <div class="bg-white border-2 border-gray-200 overflow-hidden shadow-md sm:rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs font-semibold text-gray-700 uppercase bg-gray-50 border-b-2 border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4">Cliente</th>
                                <th scope="col" class="px-6 py-4">Contacto</th>
                                <th scope="col" class="px-6 py-4 text-center">Lotes Asignados</th>
                                <th scope="col" class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($clients as $client)
                                <tr class="bg-white hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center shadow-md">
                                                <span class="text-white font-bold text-sm">{{ strtoupper(substr($client->name, 0, 2)) }}</span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-semibold text-gray-900">{{ $client->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-700">
                                            @if($client->email)
                                                <div class="flex items-center text-sm">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                    </svg>
                                                    {{ $client->email }}
                                                </div>
                                            @endif
                                            @if($client->phone)
                                                <div class="flex items-center text-sm mt-1">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    {{ $client->phone }}
                                                </div>
                                            @endif
                                            @if(!$client->email && !$client->phone)
                                                <span class="text-gray-400 text-sm italic">Sin contacto</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 shadow-sm">
                                            {{ $client->lots_count }} {{ $client->lots_count == 1 ? 'lote' : 'lotes' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('clients.show', $client) }}" class="inline-flex items-center px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 font-medium text-xs rounded-lg transition-colors duration-150">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                Estado
                                            </a>
                                            <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium text-xs rounded-lg transition-colors duration-150">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Editar
                                            </a>
                                            <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Seguro que deseas eliminar a este cliente?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 font-medium text-xs rounded-lg transition-colors duration-150">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="bg-gray-100 w-20 h-20 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-600 font-medium mb-2">No se encontraron clientes</p>
                                            @if(!request('search'))
                                                <a href="{{ route('clients.create') }}" class="mt-2 inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold text-sm group">
                                                    Crear el primer cliente
                                                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </a>
                                            @else
                                                <p class="text-gray-400 text-sm">Intenta con otros términos de búsqueda</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($clients->hasPages())
                    <div class="p-6 border-t-2 border-gray-200 bg-gray-50">
                        {{ $clients->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>