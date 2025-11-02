<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Service;
use App\Models\Lot;
use App\Http\Controllers\PaymentPlanController;
use Illuminate\Support\Facades\DB;

class LotAndPaymentPlanSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();
        $services = Service::all();

        if ($clients->isEmpty() || $services->isEmpty()) {
            $this->command->info('No hay clientes o servicios para asignar lotes. Ejecuta ClientSeeder y ServiceSeeder primero.');
            return;
        }

        $paymentPlanController = new PaymentPlanController();

        // Crear 15 lotes y planes de pago
        for ($i = 1; $i <= 15; $i++) {
            DB::transaction(function () use ($clients, $services, $paymentPlanController, $i) {
                $client = $clients->random();
                $service = $services->random();

                $lot = Lot::create([
                    'client_id' => $client->id,
                    'block_number' => rand(1, 10),
                    'lot_number' => $i,
                    'total_price' => rand(50000, 200000),
                    'status' => 'vendido',
                ]);

                $paymentPlan = $lot->paymentPlans()->create([
                    'service_id' => $service->id,
                    'total_amount' => $lot->total_price,
                    'number_of_installments' => 12,
                    'start_date' => now()->subMonths(rand(1, 6))->startOfMonth(),
                ]);

                $paymentPlanController->generateInstallments($paymentPlan);
            });
        }
    }
}