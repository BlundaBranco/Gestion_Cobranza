<!-- _history-section.blade.php - Con columna de notas -->
<div class="flex items-center gap-3 mb-6">
    <div class="p-2 bg-indigo-100 rounded-lg">
        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <div>
        <h3 class="text-lg font-bold text-gray-900">Historial de Propietarios</h3>
        <p class="text-sm text-gray-600">Registro de transferencias del lote</p>
    </div>
</div>

<div class="border-t-2 border-gray-100 pt-6">
    <div class="overflow-x-auto rounded-xl border-2 border-gray-200">
        <table class="w-full text-sm">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b-2 border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left font-bold">Fecha</th>
                    <th class="px-6 py-3 text-left font-bold">Propietario Anterior</th>
                    <th class="px-6 py-3 text-left font-bold">Nuevo Propietario</th>
                    <th class="px-6 py-3 text-left font-bold">Notas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($lot->ownershipHistory as $history)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 text-gray-900 font-semibold">
                            {{ \Illuminate\Support\Carbon::parse($history->transfer_date)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-xs">
                                        {{ $history->previousClient ? substr($history->previousClient->name, 0, 2) : 'NA' }}
                                    </span>
                                </div>
                                <span class="text-gray-700">{{ $history->previousClient->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold text-xs">
                                        {{ $history->newClient ? substr($history->newClient->name, 0, 2) : 'NA' }}
                                    </span>
                                </div>
                                <span class="text-gray-900 font-semibold">{{ $history->newClient->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            @if($history->notes)
                                <span class="text-sm">{{ $history->notes }}</span>
                            @else
                                <span class="text-sm text-gray-400 italic">Sin notas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12">
                            <div class="flex flex-col items-center justify-center text-center">
                                <div class="p-3 bg-gray-100 rounded-full mb-3">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-900 font-semibold mb-1">No hay historial</p>
                                <p class="text-sm text-gray-600">Este lote no ha sido transferido</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>