<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reporte de Todas las Cuotas Vencidas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-gray-600">Mostrando todas las cuotas con estado "vencida" que tienen un saldo pendiente.</p>
                </div>
                <div class="overflow-x-auto">
                    {{-- Usaremos la misma tabla que en el dashboard --}}
                    @include('components.dashboard.overdue-installments-table', ['installments' => $overdueInstallments])
                </div>
                <div class="p-6 border-t">
                    {{ $overdueInstallments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>