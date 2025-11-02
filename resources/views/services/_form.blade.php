<form method="POST" action="{{ $action }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="name" :value="__('Nombre')" />
        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $service->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="description" :value="__('Descripción')" />
        <textarea id="description" name="description" class="block mt-1 w-full border-2 border-gray-300 text-gray-900 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-150" rows="4">{{ old('description', $service->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="flex items-center justify-end mt-6">
        <x-primary-button>
            {{ $buttonText }}
        </x-primary-button>
    </div>
</form>