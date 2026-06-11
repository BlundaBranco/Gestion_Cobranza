<?php

namespace Tests\Feature;

use App\Exports\IncomeExport;
use App\Models\Client;
use App\Models\Installment;
use App\Models\Lot;
use App\Models\Owner;
use App\Models\PaymentPlan;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class IncomeReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $collector;
    protected Client $client;
    protected Owner $owner;
    protected Lot $lot;
    protected PaymentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'name'     => 'Admin Reporte',
            'role'     => 'admin',
        ]);
        $this->collector = User::factory()->create([
            'username' => 'cobrador_' . uniqid(),
            'name'     => 'Cobrador Uno',
            'role'     => 'user',
        ]);

        $this->owner  = Owner::create(['name' => 'SECC Test ' . uniqid()]);
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

    private function cobrar(User $as, float $amount, Installment $installment): Transaction
    {
        $response = $this->actingAs($as)->post(route('transactions.store'), [
            'client_id'    => $this->client->id,
            'amount_paid'  => $amount,
            'payment_date' => now()->format('Y-m-d'),
            'installments' => [$installment->id],
        ]);
        $response->assertSessionHas('success');

        return Transaction::latest('id')->firstOrFail();
    }

    // ── Columna Usuario ──────────────────────────────────────────────

    public function test_reporte_muestra_columna_usuario_con_nombre_del_cobrador(): void
    {
        $this->cobrar($this->collector, 100, $this->makeInstallment(100, 1));

        $response = $this->actingAs($this->admin)->get(route('reports.income'));

        $response->assertOk();
        $response->assertSee('Usuario');
        $response->assertSee('Cobrador Uno');
    }

    public function test_reporte_muestra_na_para_transaccion_sin_usuario(): void
    {
        // Transacciones previas a oct/2025 no tienen user_id
        Transaction::create([
            'client_id'    => $this->client->id,
            'amount_paid'  => 50,
            'payment_date' => now()->format('Y-m-d'),
            'status'       => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.income'));

        $response->assertOk();
        $response->assertSee('N/A');
    }

    public function test_excel_incluye_columna_usuario_en_headings_y_map(): void
    {
        $transaction = $this->cobrar($this->collector, 100, $this->makeInstallment(100, 1));

        $export   = new IncomeExport(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $headings = $export->headings();

        $this->assertContains('USUARIO', $headings);
        $this->assertSame('USUARIO', $headings[count($headings) - 2]); // anteúltima, antes de ESTADO

        $row = $export->map($transaction->load(['client', 'owner', 'user', 'installments.paymentPlan.lot', 'installments.transactions']));
        $this->assertSame('Cobrador Uno', $row[count($row) - 2]);
        $this->assertCount(count($headings), $row);
    }

    public function test_excel_rama_extra_incluye_usuario(): void
    {
        $transaction = Transaction::create([
            'client_id'    => $this->client->id,
            'user_id'      => $this->collector->id,
            'amount_paid'  => 200,
            'payment_date' => now()->format('Y-m-d'),
            'type'         => 'extra',
            'status'       => 'active',
            'notes'        => 'Cobro suelto',
        ]);

        $export = new IncomeExport(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $row    = $export->map($transaction->load(['client', 'owner', 'user', 'installments']));

        $this->assertSame('Cobrador Uno', $row[count($row) - 2]);
        $this->assertCount(count($export->headings()), $row);
    }

    // ── Corte para no-admin ──────────────────────────────────────────

    public function test_no_admin_puede_descargar_su_corte(): void
    {
        Excel::fake();
        $this->cobrar($this->collector, 100, $this->makeInstallment(100, 1));

        $response = $this->actingAs($this->collector)->get(route('reports.export'));

        $response->assertOk();
        Excel::assertDownloaded('reporte_ingresos.xlsx');
    }

    public function test_corte_de_no_admin_solo_incluye_sus_propios_cobros(): void
    {
        Excel::fake();
        $mine   = $this->cobrar($this->collector, 100, $this->makeInstallment(100, 1));
        $others = $this->cobrar($this->admin, 200, $this->makeInstallment(200, 2));

        $this->actingAs($this->collector)->get(route('reports.export'))->assertOk();

        Excel::assertDownloaded('reporte_ingresos.xlsx', function (IncomeExport $export) use ($mine) {
            $rows = $export->query()->get();

            return $rows->count() === 1 && $rows->first()->id === $mine->id;
        });
    }

    public function test_no_admin_no_puede_desfiltrar_su_corte_por_url(): void
    {
        Excel::fake();
        $this->cobrar($this->collector, 100, $this->makeInstallment(100, 1));
        $this->cobrar($this->admin, 200, $this->makeInstallment(200, 2));

        // Intento de inyectar parámetros: el filtro por user_id se fuerza igual
        $this->actingAs($this->collector)
            ->get(route('reports.export', ['user_id' => $this->admin->id]))
            ->assertOk();

        Excel::assertDownloaded('reporte_ingresos.xlsx', function (IncomeExport $export) {
            return $export->query()->get()->every(fn ($t) => $t->user_id === $this->collector->id);
        });
    }

    public function test_admin_exporta_todo_sin_filtro_de_usuario(): void
    {
        Excel::fake();
        $this->cobrar($this->collector, 100, $this->makeInstallment(100, 1));
        $this->cobrar($this->admin, 200, $this->makeInstallment(200, 2));

        $this->actingAs($this->admin)->get(route('reports.export'))->assertOk();

        Excel::assertDownloaded('reporte_ingresos.xlsx', function (IncomeExport $export) {
            return $export->query()->count() === 2;
        });
    }

    public function test_no_admin_ve_boton_de_corte_y_no_ve_totales(): void
    {
        $response = $this->actingAs($this->collector)->get(route('reports.income'));

        $response->assertOk();
        $response->assertSee('Descargar mi corte');
        $response->assertDontSee('Total Ingresado');
        $response->assertDontSee('Exportar Excel');
    }

    public function test_admin_sigue_viendo_export_completo_y_totales(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.income'));

        $response->assertOk();
        $response->assertSee('Exportar Excel');
        $response->assertSee('Total Ingresado');
        $response->assertDontSee('Descargar mi corte');
    }
}
