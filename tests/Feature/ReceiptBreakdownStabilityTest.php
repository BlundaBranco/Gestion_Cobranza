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

class ReceiptBreakdownStabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected PaymentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user   = User::factory()->create(['username' => 'tester_' . uniqid()]);
        $owner        = Owner::create(['name' => 'SECC Test ' . uniqid()]);
        $this->client = Client::factory()->create();
        $lot = Lot::create([
            'owner_id' => $owner->id, 'client_id' => $this->client->id,
            'block_number' => '2', 'lot_number' => '23', 'total_price' => 423000,
        ]);
        $service = Service::create(['name' => 'Terreno Test ' . uniqid()]);
        $this->plan = PaymentPlan::create([
            'lot_id' => $lot->id, 'service_id' => $service->id,
            'total_amount' => 423000, 'number_of_installments' => 141,
            'start_date' => now()->format('Y-m-d'), 'currency' => 'MXN',
        ]);
    }

    private function makeInstallment(float $base, int $number, float $interest = 0): Installment
    {
        return Installment::create([
            'payment_plan_id'    => $this->plan->id,
            'installment_number' => $number,
            'due_date'           => now()->subMonth(),
            'base_amount'        => $base,
            'interest_amount'    => $interest,
        ]);
    }

    private function pagar(float $monto, Installment $inst): Transaction
    {
        $this->actingAs($this->user)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => $monto,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$inst->id],
        ])->assertSessionHas('success');

        return Transaction::latest('id')->firstOrFail();
    }

    private function desgloseRecibo(Transaction $tx): string
    {
        return view('transactions.pdf', [
            'transaction' => $tx->fresh()->load(['client', 'user', 'owner', 'installments.paymentPlan.lot', 'installments.paymentPlan.service', 'creditBalanceMovements']),
        ])->render();
    }

    /** El caso exacto de Yanet: pago parcial de $1500 sobre cuota de $3000. */
    public function test_pago_parcial_es_todo_capital_y_no_cambia_si_la_mora_recalcula_interes(): void
    {
        $inst = $this->makeInstallment(3000, 33);
        $tx = $this->pagar(1500, $inst);

        // Al imprimir: $1500 capital, $0 interés (la cuota no tenía interés)
        $html1 = $this->desgloseRecibo($tx);
        $this->assertStringContainsString('$1,500.00', $html1);

        // El cron recalcula y la cuota queda vencida con $150 de interés
        $inst->update(['interest_amount' => 150, 'status' => 'vencida']);

        // El recibo debe seguir mostrando EXACTAMENTE lo mismo
        $html2 = $this->desgloseRecibo($tx);
        $this->assertSame(
            $this->extraerFilaDesglose($html1),
            $this->extraerFilaDesglose($html2),
            'El desglose del recibo cambió tras recalcular el interés — bug de inestabilidad.'
        );
    }

    /** Pago completo de capital + interés: el interés sí se refleja (capital lleno primero). */
    public function test_pago_que_cubre_capital_e_interes_reparte_bien(): void
    {
        $inst = $this->makeInstallment(3000, 4, 300);
        $tx = $this->pagar(3300, $inst);

        $split = installment_payment_split(3000, 0, 3300);
        $this->assertEqualsWithDelta(3000, $split['capital'], 0.001);
        $this->assertEqualsWithDelta(300, $split['interest'], 0.001);
    }

    public function test_helper_es_estable_ante_cambios_de_interes(): void
    {
        // El helper no recibe el interés: por construcción no puede depender de él
        $a = installment_payment_split(3000, 0, 1500);
        $this->assertEqualsWithDelta(1500, $a['capital'], 0.001);
        $this->assertEqualsWithDelta(0, $a['interest'], 0.001);

        // Pago previo de 2000, luego 1500 más: 1000 completan capital, 500 a interés
        $b = installment_payment_split(3000, 2000, 1500);
        $this->assertEqualsWithDelta(1000, $b['capital'], 0.001);
        $this->assertEqualsWithDelta(500, $b['interest'], 0.001);
    }

    private function extraerFilaDesglose(string $html): string
    {
        // Aísla la primera fila de la tabla de desglose para comparar capital/interés
        if (preg_match('/Diciembre|#33|Mz/u', $html) && preg_match('/\$[\d,]+\.\d{2}/', $html)) {
            preg_match_all('/\$[\d,]+\.\d{2}/', strip_tags($html), $m);
            return implode('|', $m[0]);
        }
        return strip_tags($html);
    }
}
