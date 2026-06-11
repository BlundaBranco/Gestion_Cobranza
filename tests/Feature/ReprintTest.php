<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Installment;
use App\Models\Lot;
use App\Models\Owner;
use App\Models\PaymentPlan;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReprintTest extends TestCase
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

    private function makeTransaction(): Transaction
    {
        $installment = Installment::create([
            'payment_plan_id'    => $this->plan->id,
            'installment_number' => 1,
            'due_date'           => now()->addMonth(),
            'base_amount'        => 100,
            'interest_amount'    => 0,
        ]);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 100,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ])->assertSessionHas('success');

        return Transaction::latest('id')->firstOrFail();
    }

    public function test_primera_impresion_es_original_e_incrementa_contador(): void
    {
        $transaction = $this->makeTransaction();
        $this->assertSame(0, (int) $transaction->print_count);

        $this->actingAs($this->user)
            ->get(route('transactions.pdf', $transaction->id))
            ->assertOk();

        $transaction->refresh();
        $this->assertSame(1, (int) $transaction->print_count);
        $this->assertNotNull($transaction->last_printed_at);
    }

    public function test_segunda_impresion_incrementa_a_dos(): void
    {
        $transaction = $this->makeTransaction();

        $this->actingAs($this->user)->get(route('transactions.pdf', $transaction->id))->assertOk();
        $this->actingAs($this->user)->get(route('transactions.pdf', $transaction->id))->assertOk();

        $this->assertSame(2, (int) $transaction->fresh()->print_count);
    }

    public function test_vista_sin_flag_no_muestra_reimpresion(): void
    {
        $transaction = $this->makeTransaction();

        $html = view('transactions.pdf', [
            'transaction' => $transaction->fresh()->load(['client', 'user', 'owner', 'installments.paymentPlan.lot', 'installments.paymentPlan.service', 'creditBalanceMovements']),
            'isReprint'   => false,
        ])->render();

        $this->assertStringNotContainsString('REIMPRESIÓN', $html);
    }

    public function test_vista_con_flag_muestra_reimpresion_en_ambas_copias(): void
    {
        $transaction = $this->makeTransaction();

        $html = view('transactions.pdf', [
            'transaction' => $transaction->fresh()->load(['client', 'user', 'owner', 'installments.paymentPlan.lot', 'installments.paymentPlan.service', 'creditBalanceMovements']),
            'isReprint'   => true,
        ])->render();

        // Una marca por copia: ORIGINAL CLIENTE y COPIA EMPRESA
        $this->assertSame(2, substr_count($html, 'REIMPRESIÓN'));
    }

    public function test_recibo_cancelado_tambien_cuenta_impresiones(): void
    {
        $transaction = $this->makeTransaction();
        $transaction->update(['status' => 'cancelled']);
        $transaction->delete(); // soft delete, como hace el destroy real

        $this->actingAs($this->user)->get(route('transactions.pdf', $transaction->id))->assertOk();
        $this->actingAs($this->user)->get(route('transactions.pdf', $transaction->id))->assertOk();

        $fresh = Transaction::withTrashed()->find($transaction->id);
        $this->assertSame(2, (int) $fresh->print_count);
    }

    public function test_pdf_extra_tambien_marca_reimpresion(): void
    {
        $transaction = Transaction::create([
            'client_id'    => $this->client->id,
            'user_id'      => $this->user->id,
            'owner_id'     => $this->owner->id,
            'amount_paid'  => 250,
            'payment_date' => now()->format('Y-m-d'),
            'type'         => 'extra',
            'currency'     => 'MXN',
            'status'       => 'active',
            'notes'        => 'Cobro extra de prueba',
        ]);

        $this->actingAs($this->user)->get(route('transactions.pdf', $transaction->id))->assertOk();
        $this->assertSame(1, (int) $transaction->fresh()->print_count);

        $html = view('transactions.pdf_extra', [
            'transaction' => $transaction->fresh()->load(['client', 'user', 'owner']),
            'isReprint'   => true,
        ])->render();

        $this->assertSame(2, substr_count($html, 'REIMPRESIÓN'));
    }
}
