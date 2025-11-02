<form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif
    <div class="p-6 space-y-6">
        <div>
            <x-input-label for="name" value="Nombre del Socio" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $owner->name)" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="contact_info" value="Información de Contacto (Opcional)" />
            <x-text-input id="contact_info" name="contact_info" class="mt-1 block w-full" :value="old('contact_info', $owner->contact_info)" />
            <x-input-error :messages="$errors->get('contact_info')" class="mt-2" />
        </div>
    </div>
    <div class="flex items-center justify-end gap-4 bg-gray-50 px-6 py-4 border-t">
        <a href="{{ route('owners.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-xs font-semibold uppercase rounded-md hover:bg-gray-500">Cancelar</a>
        <x-primary-button>{{ $buttonText }}</x-primary-button>
    </div>
</form>