<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CreditBalanceMovement;
use App\Models\Installment;
use App\Models\Lot;
use App\Models\Owner;
use App\Models\PaymentPlan;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected Owner $owner;
    protected Lot $lot;
    protected PaymentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create(['username' => 'tester_' . uniqid()]);
        $this->owner = Owner::create(['name' => 'SECC Test ' . uniqid()]);
        $this->client = Client::factory()->create();
        $this->lot = Lot::create([
            'owner_id'     => $this->owner->id,
            'client_id'    => $this->client->id,
            'block_number' => '1',
            'lot_number'   => '1',
            'total_price'  => 10000,
        ]);
        $service = Service::create(['name' => 'Servicio Test ' . uniqid()]);
        $this->plan = PaymentPlan::create([
            'lot_id'                 => $this->lot->id,
            'service_id'             => $service->id,
            'total_amount'           => 10000,
            'number_of_installments' => 10,
            'start_date'             => now()->format('Y-m-d'),
        ]);
    }

    private function makeInstallment(float $amount, int $number): Installment
    {
        return Installment::create([
            'payment_plan_id'    => $this->plan->id,
            'installment_number' => $number,
            'due_date'           => now()->addMonths($number),
            'base_amount'        => $amount,
            'interest_amount'    => 0,
        ]);
    }

    public function test_pago_excedente_genera_credit_balance(): void
    {
        $installment = $this->makeInstallment(100, 1);

        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 150,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->client->refresh();
        $this->assertEqualsWithDelta(50.0, (float) $this->client->credit_balance, 0.001);
        $this->assertSame('pagada', $installment->fresh()->status);

        $movement = CreditBalanceMovement::where('client_id', $this->client->id)->first();
        $this->assertNotNull($movement);
        $this->assertSame('added', $movement->type);
        $this->assertEqualsWithDelta(50.0, (float) $movement->amount, 0.001);
    }

    public function test_aplicar_credit_balance_cubre_cuota_completa(): void
    {
        $this->client->update(['credit_balance' => 100]);
        $installment = $this->makeInstallment(100, 1);

        // sanity: el cliente tiene credit_balance=100 antes del POST
        $this->assertEqualsWithDelta(100.0, (float) $this->client->fresh()->credit_balance, 0.001);

        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 0,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
            'apply_credit' => 1,
        ]);

        $response->assertRedirect();
        // Si redirigió a la página de errores, mostrar qué pasó
        $errs = session('error');
        $this->assertNull($errs, "Hubo error en el flujo: $errs");

        $this->client->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $this->client->credit_balance, 0.001);
        $this->assertSame('pagada', $installment->fresh()->status);

        $movement = CreditBalanceMovement::where('client_id', $this->client->id)->first();
        $this->assertSame('applied', $movement->type);
        $this->assertEqualsWithDelta(-100.0, (float) $movement->amount, 0.001);
    }

    public function test_aplicar_credit_balance_parcial_combinado_con_cash(): void
    {
        $this->client->update(['credit_balance' => 30]);
        $installment = $this->makeInstallment(100, 1);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 70,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
            'apply_credit' => 1,
        ])->assertRedirect();

        $this->client->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $this->client->credit_balance, 0.001);
        $this->assertSame('pagada', $installment->fresh()->status);

        $applied = CreditBalanceMovement::where('type', 'applied')->first();
        $this->assertNotNull($applied);
        $this->assertEqualsWithDelta(-30.0, (float) $applied->amount, 0.001);

        $this->assertFalse(CreditBalanceMovement::where('type', 'added')->exists());
    }

    public function test_pago_exacto_sin_credito_no_genera_movimientos(): void
    {
        $installment = $this->makeInstallment(100, 1);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 100,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ])->assertRedirect();

        $this->client->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $this->client->credit_balance, 0.001);
        $this->assertSame(0, CreditBalanceMovement::count());
    }

    public function test_amount_paid_registra_solo_efectivo_no_incluye_credito_aplicado(): void
    {
        $this->client->update(['credit_balance' => 30]);
        $installment = $this->makeInstallment(100, 1);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 70,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
            'apply_credit' => 1,
        ])->assertRedirect();

        $tx = Transaction::first();
        // amount_paid debe reflejar solo el efectivo recibido, no la suma con el crédito.
        $this->assertEqualsWithDelta(70.0, (float) $tx->amount_paid, 0.001);
    }

    public function test_cancelar_transaccion_con_excedente_revierte_credit_balance(): void
    {
        $installment = $this->makeInstallment(100, 1);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 150,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ]);

        $this->client->refresh();
        $this->assertEqualsWithDelta(50.0, (float) $this->client->credit_balance, 0.001);

        $tx = Transaction::first();
        $this->actingAs($this->user)->delete(route('transactions.destroy', $tx))->assertRedirect();

        $this->client->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $this->client->credit_balance, 0.001);
        $this->assertSame(0, CreditBalanceMovement::count());
    }

    public function test_cancelar_transaccion_con_credito_aplicado_devuelve_el_credito(): void
    {
        $this->client->update(['credit_balance' => 100]);
        $installment = $this->makeInstallment(100, 1);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 0,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
            'apply_credit' => 1,
        ]);

        $this->client->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $this->client->credit_balance, 0.001);

        $tx = Transaction::first();
        $this->actingAs($this->user)->delete(route('transactions.destroy', $tx))->assertRedirect();

        $this->client->refresh();
        $this->assertEqualsWithDelta(100.0, (float) $this->client->credit_balance, 0.001);
    }

    public function test_cancelar_bloqueada_si_credito_generado_ya_fue_consumido(): void
    {
        // Tx1: genera 50 de crédito
        $i1 = $this->makeInstallment(100, 1);
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 150,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$i1->id],
        ]);

        // Tx2: consume el crédito
        $i2 = $this->makeInstallment(50, 2);
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 0,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$i2->id],
            'apply_credit' => 1,
        ]);

        $this->client->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $this->client->credit_balance, 0.001);

        // Cancelar Tx1 debe fallar (el crédito ya fue gastado)
        $tx1 = Transaction::orderBy('id')->first();
        $response = $this->actingAs($this->user)->delete(route('transactions.destroy', $tx1));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Estado intacto
        $this->client->refresh();
        $this->assertEqualsWithDelta(0.0, (float) $this->client->credit_balance, 0.001);
        $this->assertNotNull(Transaction::find($tx1->id), 'tx1 sigue existiendo (no fue soft-deleted)');
    }

    public function test_validacion_rechaza_monto_total_cero_sin_credito(): void
    {
        $installment = $this->makeInstallment(100, 1);

        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 0,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Transaction::count());
    }
}
