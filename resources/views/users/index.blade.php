<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Administración de Usuarios</h2>
                <p class="text-sm text-gray-600">Gestiona contraseñas de acceso</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Usuarios del sistema</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    @foreach ($users as $user)
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                    <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full
                                        {{ $user->isAdmin() ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $user->isAdmin() ? 'Admin' : 'Usuario' }}
                                    </span>
                                </div>

                                <form method="POST" action="{{ route('users.update-password', $user) }}"
                                      class="flex flex-col sm:flex-row gap-2 sm:items-end"
                                      x-data="{ loading: false }" @submit="loading = true">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <x-input-label :for="'password_' . $user->id" value="Nueva contraseña" class="text-xs" />
                                        <x-text-input
                                            :id="'password_' . $user->id"
                                            name="password"
                                            type="password"
                                            class="mt-1 block w-full sm:w-48 text-sm"
                                            placeholder="Mínimo 6 caracteres"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <x-input-label :for="'password_confirmation_' . $user->id" value="Confirmar contraseña" class="text-xs" />
                                        <x-text-input
                                            :id="'password_confirmation_' . $user->id"
                                            name="password_confirmation"
                                            type="password"
                                            class="mt-1 block w-full sm:w-48 text-sm"
                                            placeholder="Repetir contraseña"
                                            required
                                        />
                                    </div>

                                    <button type="submit"
                                            :disabled="loading"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-medium rounded-lg transition whitespace-nowrap">
                                        <span x-show="!loading">Actualizar</span>
                                        <span x-show="loading">Guardando...</span>
                                    </button>
                                </form>
                            </div>

                            @if ($errors->any() && old('_user_id') == $user->id)
                                <p class="mt-2 text-sm text-red-600">{{ $errors->first('password') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
