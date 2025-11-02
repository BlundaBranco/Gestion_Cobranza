<!-- _form.blade.php -->
<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="identifier" :value="__('Identificador (Ej: Manzana 1, Lote 5)')" />
        <x-text-input id="identifier" class="block mt-1 w-full" type="text" name="identifier" :value="old('identifier', $lot->identifier)" required autofocus />
        <x-input-error :messages="$errors->get('identifier')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="total_price" :value="__('Precio Total')" />
        <x-text-input id="total_price" class="block mt-1 w-full" type="number" step="0.01" name="total_price" :value="old('total_price', $lot->total_price)" required />
        <x-input-error :messages="$errors->get('total_price')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="status" :value="__('Estado')" />
        <select id="status" name="status" class="block mt-1 w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150">
            <option value="disponible" @selected(old('status', $lot->status) == 'disponible')>Disponible</option>
            <option value="vendido" @selected(old('status', $lot->status) == 'vendido')>Vendido</option>
            <option value="liquidado" @selected(old('status', $lot->status) == 'liquidado')>Liquidado</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="client_id" :value="__('Cliente Asignado')" />

        @if($lot->exists)
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
            <p class="mt-2 text-sm text-gray-600">Para cambiar el propietario, utiliza la sección "Transferir Lote".</p>
            <input type="hidden" name="client_id" value="{{ $lot->client_id }}">
        @else
            <select id="client_id" name="client_id" class="block mt-1 w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150">
                <option value="">-- Sin asignar --</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id', $lot->client_id) == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
        @endif
    </div>

    <div class="flex items-center justify-end mt-6">
        <x-primary-button>
            {{ $buttonText }}
        </x-primary-button>
    </div>
</form>