<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-sm">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Cuotas Vencidas</h2>
                <p class="text-sm text-gray-600">Todas las cuotas con saldo pendiente</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-red-50 to-orange-50 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">Filtros</h3>
                    </div>
                </div>
                <form method="GET" action="{{ route('reports.overdue') }}" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div>
                            <x-input-label for="owner_id" value="Socio" />
                            <select id="owner_id" name="owner_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">Todos los Socios</option>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected($selectedOwner == $owner->id)>
                                        {{ $owner->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="block_number" value="Manzana" />
                            <x-text-input id="block_number" name="block_number" type="text" class="mt-1 block w-full text-sm" placeholder="Ej: A1" :value="$blockNumber" />
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button class="flex-1 justify-center h-[42px]">Filtrar</x-primary-button>
                            <a href="{{ route('reports.overdue.export', request()->query()) }}"
                               class="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 transition shadow-sm h-[42px]">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    @include('components.dashboard.overdue-installments-table', ['installments' => $overdueInstallments])
                </div>
                <div class="p-6 border-t">
                    {{ $overdueInstallments->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
