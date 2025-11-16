<div class="flex items-center gap-3 mb-6">
    <div class="p-2 bg-purple-100 rounded-lg">
        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
    </div>
    <div>
        <h3 class="text-lg font-bold text-gray-900">Transferir Lote</h3>
        <p class="text-sm text-gray-600">Cambia el propietario del lote</p>
    </div>
</div>

<form method="POST" action="{{ route('lots.transfer', $lot) }}" class="border-t-2 border-gray-100 pt-6">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="new_client_id" value="Nuevo Propietario" />
            <select name="new_client_id" id="new_client_id" class="select2 mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" required>
                <option value="">Seleccionar cliente</option>
                @foreach($clients->where('id', '!=', $lot->client_id) as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('new_client_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="transfer_date" value="Fecha de Transferencia" />
            <x-text-input id="transfer_date" name="transfer_date" type="date" class="mt-1 block w-full" :value="date('Y-m-d')" required />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="notes" value="Notas (Opcional)" />
            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" placeholder="Agrega notas sobre la transferencia..."></textarea>
        </div>
    </div>
    <div class="flex justify-end mt-6">
        <x-primary-button>
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            Transferir
        </x-primary-button>
    </div>
</form>