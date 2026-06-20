<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Installment;
use App\Models\Lot;
use App\Models\Owner;
use App\Models\PaymentPlan;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentRescheduleTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $owner   = Owner::create(['name' => 'SECC Test ' . uniqid()]);
        $client  = Client::factory()->create();
        $lot = Lot::create([
            'owner_id' => $owner->id, 'client_id' => $client->id,
            'block_number' => '8', 'lot_number' => '18', 'total_price' => 500000,
        ]);
        $service = Service::create(['name' => 'Terreno Test ' . uniqid()]);
        $this->plan = PaymentPlan::create([
            'lot_id' => $lot->id, 'service_id' => $service->id,
            'total_amount' => 500000, 'number_of_installments' => 23,
            'start_date' => now()->format('Y-m-d'), 'currency' => 'MXN',
        ]);
    }

    private function makeInstallment(string $dueDate, string $status, float $interest = 0): Installment
    {
        return Installment::create([
            'payment_plan_id'    => $this->plan->id,
            'installment_number' => 1,
            'due_date'           => $dueDate,
            'base_amount'        => 3400,
            'interest_amount'    => $interest,
            'status'             => $status,
        ]);
    }

    public function test_cuota_vencida_reprogramada_al_futuro_vuelve_a_pendiente(): void
    {
        // Estaba vencida (con interés de mora) y se le movió la fecha a 2027
        $inst = $this->makeInstallment(now()->addYear()->format('Y-m-d'), 'vencida', 340);

        $this->artisan('installments:update-status')->assertExitCode(0);

        $inst->refresh();
        $this->assertSame('pendiente', $inst->status);
        $this->assertEqualsWithDelta(0, (float) $inst->interest_amount, 0.001);
    }

    public function test_cuota_genuinamente_vencida_sigue_vencida(): void
    {
        $inst = $this->makeInstallment(now()->subMonths(2)->format('Y-m-d'), 'pendiente');

        $this->artisan('installments:update-status')->assertExitCode(0);

        $inst->refresh();
        $this->assertSame('vencida', $inst->status);
        $this->assertEqualsWithDelta(340, (float) $inst->interest_amount, 0.001); // 10% de 3400
    }

    public function test_cuota_pagada_no_se_revierte_por_fecha(): void
    {
        $inst = $this->makeInstallment(now()->addYear()->format('Y-m-d'), 'pagada');

        $this->artisan('installments:update-status')->assertExitCode(0);

        $this->assertSame('pagada', $inst->fresh()->status);
    }

    public function test_editar_fecha_al_futuro_desde_el_controller_corrige_el_estado(): void
    {
        $user = User::factory()->create(['username' => 'u_' . uniqid(), 'role' => 'admin']);
        $inst = $this->makeInstallment(now()->subMonth()->format('Y-m-d'), 'vencida', 340);

        // La edición de fecha llama al cron internamente
        $this->actingAs($user)->put(route('installments.update', $inst), [
            'due_date' => now()->addYear()->format('Y-m-d'),
        ])->assertSessionHas('success');

        $inst->refresh();
        $this->assertSame('pendiente', $inst->status);
        $this->assertEqualsWithDelta(0, (float) $inst->interest_amount, 0.001);
    }
}
