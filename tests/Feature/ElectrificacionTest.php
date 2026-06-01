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

class ElectrificacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected Owner $socio;
    protected Owner $emisorLuz;
    protected Lot $lot;
    protected PaymentPlan $planTerreno;
    protected PaymentPlan $planLuz;

    protected function setUp(): void
    {
        parent::setUp();

        // El emisor ELECTRIFICACIÓN lo crea la migración de base de datos.
        $this->emisorLuz = Owner::where('name', Owner::NAME_ELECTRIFICACION)->firstOrFail();

        $this->user   = User::factory()->create(['username' => 'tester_' . uniqid()]);
        $this->socio  = Owner::create(['name' => 'SECC Test ' . uniqid()]);
        $this->client = Client::factory()->create();
        $this->lot = Lot::create([
            'owner_id'     => $this->socio->id,
            'client_id'    => $this->client->id,
            'block_number' => '1',
            'lot_number'   => '1',
            'total_price'  => 10000,
        ]);

        $serviceTerreno = Service::create(['name' => 'Terreno Test ' . uniqid()]);
        $serviceLuz     = Service::create([
            'name'             => 'Electrificación Test ' . uniqid(),
            'billing_owner_id' => $this->emisorLuz->id,
        ]);

        $this->planTerreno = PaymentPlan::create([
            'lot_id' => $this->lot->id, 'service_id' => $serviceTerreno->id,
            'total_amount' => 10000, 'number_of_installments' => 10, 'start_date' => now()->format('Y-m-d'),
        ]);
        $this->planLuz = PaymentPlan::create([
            'lot_id' => $this->lot->id, 'service_id' => $serviceLuz->id,
            'total_amount' => 5000, 'number_of_installments' => 10, 'start_date' => now()->format('Y-m-d'),
        ]);
    }

    private function installment(PaymentPlan $plan, float $amount, int $number): Installment
    {
        return Installment::create([
            'payment_plan_id'    => $plan->id,
            'installment_number' => $number,
            'due_date'           => now()->addMonths($number),
            'base_amount'        => $amount,
            'interest_amount'    => 0,
        ]);
    }

    public function test_emisor_electrificacion_existe_y_su_secuencia_arranca_en_cero(): void
    {
        $this->assertTrue($this->emisorLuz->is_billing_entity);
        $this->assertSame(0, (int) ($this->emisorLuz->sequence?->current_value ?? 0));
    }

    public function test_cobro_de_luz_toma_folio_del_emisor_no_del_socio(): void
    {
        $cuota = $this->installment($this->planLuz, 500, 1);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 500,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$cuota->id],
        ])->assertRedirect();

        $tx = Transaction::first();
        $this->assertSame($this->emisorLuz->id, $tx->owner_id, 'El cobro de luz debe quedar bajo el emisor ELECTRIFICACIÓN.');
        $this->assertSame('FOLIO-000001', $tx->folio_number, 'La luz arranca su numeración en 1.');
    }

    public function test_cobro_de_terreno_sigue_usando_folio_del_socio(): void
    {
        $cuota = $this->installment($this->planTerreno, 1000, 1);

        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 1000,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$cuota->id],
        ])->assertRedirect();

        $tx = Transaction::first();
        $this->assertSame($this->socio->id, $tx->owner_id, 'El cobro de terreno sigue bajo el socio del lote.');
    }

    public function test_no_permite_mezclar_luz_y_terreno_en_un_mismo_cobro(): void
    {
        $cuotaLuz     = $this->installment($this->planLuz, 500, 1);
        $cuotaTerreno = $this->installment($this->planTerreno, 1000, 1);

        $response = $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => 1500,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$cuotaLuz->id, $cuotaTerreno->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, Transaction::count(), 'No se debe crear ningún cobro cuando se mezclan emisores.');
    }

    public function test_numeracion_de_luz_es_independiente_y_consecutiva(): void
    {
        $c1 = $this->installment($this->planLuz, 500, 1);
        $c2 = $this->installment($this->planLuz, 500, 2);

        foreach ([$c1, $c2] as $c) {
            $this->actingAs($this->user)->post(route('transactions.store'), [
                'client_id'    => $this->client->id,
                'amount_paid'  => 500,
                'payment_date' => now()->format('Y-m-d'),
                'installments' => [$c->id],
            ])->assertRedirect();
        }

        $folios = Transaction::where('owner_id', $this->emisorLuz->id)->orderBy('id')->pluck('folio_number')->all();
        $this->assertSame(['FOLIO-000001', 'FOLIO-000002'], $folios);
    }

    public function test_emisor_no_aparece_como_socio_seleccionable(): void
    {
        $this->assertFalse(
            Owner::socios()->where('id', $this->emisorLuz->id)->exists(),
            'El emisor ELECTRIFICACIÓN no debe ofrecerse como dueño de lote.'
        );
        $this->assertTrue(Owner::socios()->where('id', $this->socio->id)->exists());
    }

    public function test_comando_migra_cobros_de_luz_existentes_al_emisor(): void
    {
        // Simular un cobro de luz "viejo": quedó bajo el socio con un folio de la sección.
        $cuota = $this->installment($this->planLuz, 500, 1);
        $tx = $this->client->transactions()->create([
            'amount_paid'  => 500,
            'payment_date' => now()->subMonth()->format('Y-m-d'),
            'user_id'      => $this->user->id,
            'owner_id'     => $this->socio->id,
            'folio_number' => 'FOLIO-099999',
        ]);
        $tx->installments()->attach($cuota->id, ['amount_applied' => 500]);

        $this->artisan('luz:migrar-folios')
            ->expectsQuestion("Esto modifica la BD: migra 1 cobro(s) a {$this->emisorLuz->name}. Escribí 'CONFIRMAR' para ejecutar", 'CONFIRMAR')
            ->assertExitCode(0);

        $tx->refresh();
        $this->assertSame($this->emisorLuz->id, $tx->owner_id, 'El cobro viejo de luz debe quedar bajo el emisor.');
        $this->assertSame('FOLIO-000001', $tx->folio_number, 'Debe tomar el primer folio de la numeración de luz.');
    }
}
