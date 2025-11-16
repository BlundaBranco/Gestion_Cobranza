<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-900 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Tarjetas de Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <x-dashboard.stat-card title="Total Clientes" :value="$stats['totalClients']" icon="clients" color="blue" />
                <x-dashboard.stat-card title="Total Lotes" :value="$stats['totalLots']" icon="lots" color="green" />
                <x-dashboard.stat-card title="Transacciones del Mes" :value="$stats['transactionsThisMonth']" icon="transactions" color="orange" />
            </div>

            <!-- Acciones Rápidas como Grid -->
            <div class="bg-white border-2 border-gray-200 p-8 rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
                    <h4 class="font-semibold text-lg text-gray-900">Acciones Rápidas</h4>
                    <a href="{{ route('transactions.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center group">
                        Ver transacciones
                        <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <!-- Nuevo Cliente -->
                    <a href="{{ route('clients.create') }}" class="relative group overflow-hidden p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl border-2 border-blue-200 hover:border-blue-400 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-300 rounded-full -mr-10 -mt-10 opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
                        <svg class="w-5 h-5 text-blue-400 group-hover:text-blue-600 group-hover:translate-x-1 transition-all absolute top-4 right-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <div class="relative z-10">
                            <div class="bg-blue-500 w-14 h-14 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <h5 class="font-bold text-gray-900 mb-1">Nuevo Cliente</h5>
                            <p class="text-sm text-gray-600">Registrar cliente</p>
                        </div>
                    </a>

                    <!-- Registrar Pago -->
                    <a href="{{ route('transactions.create') }}" class="relative group overflow-hidden p-6 bg-gradient-to-br from-green-50 to-emerald-100 rounded-2xl border-2 border-green-200 hover:border-green-400 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-green-300 rounded-full -mr-10 -mt-10 opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
                        <svg class="w-5 h-5 text-green-400 group-hover:text-green-600 group-hover:translate-x-1 transition-all absolute top-4 right-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <div class="relative z-10">
                            <div class="bg-green-500 w-14 h-14 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h5 class="font-bold text-gray-900 mb-1">Registrar Pago</h5>
                            <p class="text-sm text-gray-600">Procesar pago</p>
                        </div>
                    </a>

                    <!-- Nuevo Lote -->
                    <a href="{{ route('lots.create') }}" class="relative group overflow-hidden p-6 bg-gradient-to-br from-orange-50 to-amber-100 rounded-2xl border-2 border-orange-200 hover:border-orange-400 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-orange-300 rounded-full -mr-10 -mt-10 opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
                        <svg class="w-5 h-5 text-orange-400 group-hover:text-orange-600 group-hover:translate-x-1 transition-all absolute top-4 right-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <div class="relative z-10">
                            <div class="bg-orange-500 w-14 h-14 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <h5 class="font-bold text-gray-900 mb-1">Nuevo Lote</h5>
                            <p class="text-sm text-gray-600">Agregar lote</p>
                        </div>
                    </a>

                    <!-- Ver Reportes -->
                    <a href="{{ route('reports.income') }}" class="relative group overflow-hidden p-6 bg-gradient-to-br from-purple-50 to-violet-100 rounded-2xl border-2 border-purple-200 hover:border-purple-400 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-purple-300 rounded-full -mr-10 -mt-10 opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
                        <svg class="w-5 h-5 text-purple-400 group-hover:text-purple-600 group-hover:translate-x-1 transition-all absolute top-4 right-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <div class="relative z-10">
                            <div class="bg-purple-500 w-14 h-14 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <h5 class="font-bold text-gray-900 mb-1">Ver Reportes</h5>
                            <p class="text-sm text-gray-600">Consultar ingresos</p>
                        </div>
                    </a>

                </div>
            </div>
            
            <!-- Resumen del Sistema -->
            <div class="bg-white border-2 border-gray-200 p-8 rounded-xl shadow-md hover:shadow-lg transition-all duration-300">
                 <h4 class="font-semibold text-lg text-gray-900 mb-7 pb-3 border-b border-gray-100">Resumen del Sistema</h4>
                 <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="relative overflow-hidden p-6 bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 rounded-2xl border-2 border-green-200 hover:border-green-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-green-200 rounded-full -mr-12 -mt-12 opacity-30 group-hover:scale-110 transition-transform"></div>
                        <p class="text-5xl font-extrabold text-green-600 mb-3 relative z-10">{{ $systemSummary['lotes_disponibles'] }}</p>
                        <p class="text-sm font-semibold text-gray-700 relative z-10">Lotes Disponibles</p>
                    </div>
                     <div class="relative overflow-hidden p-6 bg-gradient-to-br from-yellow-50 via-amber-50 to-orange-50 rounded-2xl border-2 border-yellow-200 hover:border-yellow-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-200 rounded-full -mr-12 -mt-12 opacity-30 group-hover:scale-110 transition-transform"></div>
                        <p class="text-5xl font-extrabold text-yellow-600 mb-3 relative z-10">{{ $systemSummary['lotes_vendidos'] }}</p>
                        <p class="text-sm font-semibold text-gray-700 relative z-10">Lotes Vendidos</p>
                    </div>
                     <div class="relative overflow-hidden p-6 bg-gradient-to-br from-blue-50 via-cyan-50 to-sky-50 rounded-2xl border-2 border-blue-200 hover:border-blue-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-blue-200 rounded-full -mr-12 -mt-12 opacity-30 group-hover:scale-110 transition-transform"></div>
                        <p class="text-5xl font-extrabold text-blue-600 mb-3 relative z-10">{{ $systemSummary['lotes_liquidados'] }}</p>
                        <p class="text-sm font-semibold text-gray-700 relative z-10">Lotes Liquidados</p>
                    </div>
                     <div class="relative overflow-hidden p-6 bg-gradient-to-br from-purple-50 via-violet-50 to-indigo-50 rounded-2xl border-2 border-purple-200 hover:border-purple-300 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-purple-200 rounded-full -mr-12 -mt-12 opacity-30 group-hover:scale-110 transition-transform"></div>
                        <p class="text-5xl font-extrabold text-purple-600 mb-3 relative z-10">{{ $systemSummary['servicios_activos'] }}</p>
                        <p class="text-sm font-semibold text-gray-700 relative z-10">Servicios Activos</p>
                    </div>
                 </div>
            </div>

            <!-- Cuotas Vencidas -->
            <div class="bg-white border-2 border-gray-200 p-8 rounded-xl shadow-md">
                <div class="flex justify-between items-center mb-6 pb-3 border-b border-gray-100">
                    <h4 class="font-semibold text-lg text-gray-900">Cuotas Vencidas ({{ $overdueInstallmentsCount }})</h4>
                    <a href="{{ route('reports.overdue') }}" class="text-sm text-blue-600 hover:underline">Ver Todas</a>
                </div>
                <div class="overflow-x-auto">
                    {{-- CORRECCIÓN DE LA RUTA DEL INCLUDE --}}
                    @include('components.dashboard.overdue-installments-table', ['installments' => $overdueInstallments])
                </div>
            </div>

        </div>
    </div>
</x-app-layout>