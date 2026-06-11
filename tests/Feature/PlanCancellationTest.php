<?php

namespace Tests\Feature;

use App\Exports\OverdueInstallmentsExport;
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

class PlanCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regular;
    protected Client $client;
    protected Owner $owner;
    protected Lot $lot;
    protected Service $service;
    protected PaymentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['username' => 'admin_' . uniqid(), 'role' => 'admin']);
        $this->regular = User::factory()->create(['username' => 'user_' . uniqid(), 'role' => 'user']);
        $this->owner   = Owner::create(['name' => 'SECC Test ' . uniqid()]);
        $this->client  = Client::factory()->create();
        $this->lot = Lot::create([
            'owner_id'     => $this->owner->id,
            'client_id'    => $this->client->id,
            'block_number' => '14',
            'lot_number'   => '19',
            'status'       => 'vendido',
            'total_price'  => 10000,
        ]);
        $this->service = Service::create(['name' => 'Terreno Test ' . uniqid()]);
        $this->plan = PaymentPlan::create([
            'lot_id'                 => $this->lot->id,
            'service_id'             => $this->service->id,
            'total_amount'           => 10000,
            'number_of_installments' => 10,
            'start_date'             => now()->format('Y-m-d'),
        ]);
    }

    private function makeInstallment(float $amount, int $number, ?PaymentPlan $plan = null, ?string $dueDate = null): Installment
    {
        return Installment::create([
            'payment_plan_id'    => ($plan ?? $this->plan)->id,
            'installment_number' => $number,
            'due_date'           => $dueDate ?? now()->addMonths($number)->format('Y-m-d'),
            'base_amount'        => $amount,
            'interest_amount'    => 0,
        ]);
    }

    private function cobrar(float $amount, Installment $installment): Transaction
    {
        $this->actingAs($this->admin)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => $amount,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ])->assertSessionHas('success');

        return Transaction::latest('id')->firstOrFail();
    }

    private function cancelar(?PaymentPlan $plan = null, ?string $notes = null)
    {
        return $this->actingAs($this->admin)->post(
            route('payment-plans.cancel', $plan ?? $this->plan),
            ['cancellation_notes' => $notes]
        );
    }

    // ── Cancelación básica ───────────────────────────────────────────

    public function test_admin_puede_cancelar_plan_con_snapshot_del_cliente(): void
    {
        $this->cancelar(notes: 'Devolución por fuera del sistema')->assertSessionHas('success');

        $this->plan->refresh();
        $this->assertSame('cancelled', $this->plan->status);
        $this->assertNotNull($this->plan->cancelled_at);
        $this->assertSame($this->admin->id, $this->plan->cancelled_by);
        $this->assertSame($this->client->id, $this->plan->cancelled_client_id);
        $this->assertSame('Devolución por fuera del sistema', $this->plan->cancellation_notes);
    }

    public function test_no_admin_no_puede_cancelar(): void
    {
        $this->actingAs($this->regular)
            ->post(route('payment-plans.cancel', $this->plan))
            ->assertForbidden();

        $this->assertSame('active', $this->plan->fresh()->status);
    }

    public function test_cancelar_dos_veces_falla(): void
    {
        $this->cancelar()->assertSessionHas('success');
        $this->cancelar()->assertSessionHas('error');
    }

    public function test_lote_queda_disponible_si_no_quedan_planes_activos(): void
    {
        $this->cancelar();
        $this->assertSame('disponible', $this->lot->fresh()->status);
    }

    public function test_lote_sigue_vendido_si_queda_otro_plan_activo(): void
    {
        $luz = Service::create(['name' => 'Electrificacion Test ' . uniqid()]);
        PaymentPlan::create([
            'lot_id'                 => $this->lot->id,
            'service_id'             => $luz->id,
            'total_amount'           => 5000,
            'number_of_installments' => 5,
            'start_date'             => now()->format('Y-m-d'),
        ]);

        $this->cancelar();
        $this->assertSame('vendido', $this->lot->fresh()->status);
    }

    // ── Reventa ──────────────────────────────────────────────────────

    public function test_plan_cancelado_permite_crear_plan_nuevo_mismo_lote_y_servicio(): void
    {
        $this->cancelar();

        $response = $this->actingAs($this->admin)->post(route('lots.payment-plans.store', $this->lot), [
            'service_id' => $this->service->id,
            'currency'   => 'MXN',
            'amounts'    => [1000, 1000],
            'due_dates'  => [now()->format('Y-m-d'), now()->addMonth()->format('Y-m-d')],
            'numbers'    => [1, 2],
        ]);

        $response->assertSessionHas('success');
        $this->assertSame(2, PaymentPlan::where('lot_id', $this->lot->id)->where('service_id', $this->service->id)->count());
        $this->assertSame(1, PaymentPlan::where('lot_id', $this->lot->id)->where('service_id', $this->service->id)->where('status', 'active')->count());
    }

    public function test_plan_activo_sigue_bloqueando_duplicados(): void
    {
        $response = $this->actingAs($this->admin)->post(route('lots.payment-plans.store', $this->lot), [
            'service_id' => $this->service->id,
            'currency'   => 'MXN',
            'amounts'    => [1000],
            'due_dates'  => [now()->format('Y-m-d')],
            'numbers'    => [1],
        ]);

        $response->assertSessionHas('error');
    }

    // ── Historial intacto ────────────────────────────────────────────

    public function test_cancelar_no_toca_pagos_folios_ni_cuotas(): void
    {
        $installment = $this->makeInstallment(1000, 1);
        $transaction = $this->cobrar(1000, $installment);
        $folio = $transaction->folio_number;

        $this->cancelar();

        $transaction->refresh();
        $this->assertNull($transaction->deleted_at);
        $this->assertSame($folio, $transaction->folio_number);
        $this->assertEqualsWithDelta(1000.0, (float) $transaction->amount_paid, 0.001);
        $this->assertEqualsWithDelta(1000.0, (float) $transaction->installments()->first()->pivot->amount_applied, 0.001);
        $this->assertSame('pagada', $installment->fresh()->status);
    }

    // ── Bloqueo de cobros ────────────────────────────────────────────

    public function test_api_de_cuotas_pendientes_excluye_plan_cancelado(): void
    {
        $this->makeInstallment(1000, 1);
        $this->cancelar();

        $response = $this->actingAs($this->admin)->get(route('clients.pending-installments', $this->client));

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_no_se_puede_cobrar_cuota_de_plan_cancelado_por_post_directo(): void
    {
        $installment = $this->makeInstallment(1000, 1);
        $this->cancelar();

        $response = $this->actingAs($this->admin)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, Transaction::count());
    }

    // ── Congelamiento: intereses y liquidación ───────────────────────

    public function test_comando_no_vence_ni_recalcula_interes_de_plan_cancelado(): void
    {
        $installment = $this->makeInstallment(1000, 1, dueDate: now()->subMonths(2)->format('Y-m-d'));
        $this->cancelar();

        $this->artisan('installments:update-status')->assertExitCode(0);

        $installment->refresh();
        $this->assertSame('pendiente', $installment->status);
        $this->assertEqualsWithDelta(0.0, (float) $installment->interest_amount, 0.001);
    }

    public function test_plan_activo_sigue_venciendo_y_generando_interes(): void
    {
        $installment = $this->makeInstallment(1000, 1, dueDate: now()->subMonths(2)->format('Y-m-d'));

        $this->artisan('installments:update-status')->assertExitCode(0);

        $installment->refresh();
        $this->assertSame('vencida', $installment->status);
        $this->assertEqualsWithDelta(100.0, (float) $installment->interest_amount, 0.001); // 10% de 1000
    }

    public function test_lote_liquida_por_plan_nuevo_aunque_el_cancelado_tenga_deuda(): void
    {
        // Plan viejo con cuota impaga, cancelado
        $this->makeInstallment(1000, 1);
        $this->cancelar();

        // Plan nuevo, pagado completo
        $newPlan = PaymentPlan::create([
            'lot_id'                 => $this->lot->id,
            'service_id'             => $this->service->id,
            'total_amount'           => 500,
            'number_of_installments' => 1,
            'start_date'             => now()->format('Y-m-d'),
        ]);
        $newInstallment = $this->makeInstallment(500, 1, $newPlan);
        // refresh: cancelar() dejó el lote 'disponible' en DB y la instancia en memoria quedó stale
        $this->lot->refresh()->update(['status' => 'vendido']);
        $this->cobrar(500, $newInstallment);

        $this->artisan('installments:update-status')->assertExitCode(0);

        $this->assertSame('liquidado', $this->lot->fresh()->status);
    }

    // ── Visibilidad ──────────────────────────────────────────────────

    public function test_ficha_del_titular_original_muestra_historial_tras_revender(): void
    {
        $installment = $this->makeInstallment(1000, 1);
        $this->cobrar(1000, $installment);
        $this->cancelar(notes: 'Cliente desistió');

        // Reventa: transferir el lote a un cliente nuevo
        $newClient = Client::factory()->create();
        $this->actingAs($this->admin)->post(route('lots.transfer', $this->lot), [
            'new_client_id' => $newClient->id,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        // El titular original conserva su historial aparte
        $response = $this->actingAs($this->admin)->get(route('clients.show', $this->client));
        $response->assertOk();
        $response->assertSee('Planes cancelados');
        $response->assertSee('Cliente desistió');

        // El cliente nuevo NO hereda el historial del viejo
        $responseNew = $this->actingAs($this->admin)->get(route('clients.show', $newClient));
        $responseNew->assertOk();
        $responseNew->assertDontSee('Planes cancelados');
        $responseNew->assertDontSee('Cliente desistió');
    }

    public function test_deuda_del_plan_cancelado_no_aparece_en_ficha_del_cliente(): void
    {
        $this->makeInstallment(1000, 1);
        $this->cancelar();

        $response = $this->actingAs($this->admin)->get(route('clients.show', $this->client));

        $response->assertOk();
        // El plan cancelado no se lista como plan vigente del lote
        $response->assertSee('Este lote no tiene planes de pago.');
    }

    public function test_estado_de_cuenta_excel_no_hereda_plan_cancelado_al_cliente_nuevo(): void
    {
        $installment = $this->makeInstallment(1000, 1);
        $this->cobrar(1000, $installment);
        $this->makeInstallment(1000, 2); // cuota impaga: deuda del titular viejo
        $this->cancelar();

        $newClient = Client::factory()->create();
        $this->actingAs($this->admin)->post(route('lots.transfer', $this->lot), [
            'new_client_id' => $newClient->id,
            'transfer_date' => now()->format('Y-m-d'),
        ]);

        // El estado de cuenta del comprador nuevo no debe incluir deuda ni pagos del plan cancelado
        $data = (new \App\Exports\ClientAccountExport($newClient->fresh()))->view()->getData();
        $this->assertSame([], $data['lotSummaries'][$this->lot->id] ?? []);
    }

    public function test_indice_de_lotes_no_muestra_deuda_de_plan_cancelado(): void
    {
        $this->makeInstallment(1000, 1); // impaga
        $this->cancelar();

        $response = $this->actingAs($this->admin)->get(route('lots.index'));

        $response->assertOk();
        $response->assertDontSee($this->service->name);
    }

    public function test_resumen_financiero_del_lote_excluye_plan_cancelado(): void
    {
        $this->makeInstallment(1000, 1); // impaga
        $this->cancelar();

        $response = $this->actingAs($this->admin)->get(route('lots.edit', $this->lot));

        $response->assertOk();
        $response->assertDontSee('Deuda Pendiente'); // sin planes activos, el resumen no renderiza
        $response->assertSee('Planes cancelados');   // el historial sí
    }

    public function test_reporte_de_vencidas_excluye_planes_cancelados(): void
    {
        $installment = $this->makeInstallment(1000, 1, dueDate: now()->subMonths(2)->format('Y-m-d'));
        $installment->update(['status' => 'vencida']);
        $this->cancelar();

        $this->actingAs($this->admin)
            ->get(route('reports.overdue'))
            ->assertOk()
            ->assertDontSee($this->client->name);

        $export = new OverdueInstallmentsExport();
        $this->assertSame(0, $export->query()->count());
    }
}
