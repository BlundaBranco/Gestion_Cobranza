<?php

namespace App\Console\Commands;

use App\Models\OwnerSequence;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarFoliosLuz extends Command
{
    protected $signature = 'luz:migrar-folios
        {--dry-run : muestra el plan sin tocar la BD}';

    protected $description = 'Migra los cobros de servicios con emisor propio (ej. Electrificación) a la numeración de ese emisor, en orden cronológico, liberando los folios que ocupan en los socios. Solo migra cobros puros (no mixtos).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $services = Service::whereNotNull('billing_owner_id')->with('billingOwner')->get();
        if ($services->isEmpty()) {
            $this->error('No hay servicios con emisor de folios propio. Corré primero la migración de base de datos.');
            return 1;
        }

        $totalMigradas = 0;

        foreach ($services->groupBy('billing_owner_id') as $billingOwnerId => $svcGroup) {
            $emisor = $svcGroup->first()->billingOwner;
            if (! $emisor) {
                continue;
            }
            $serviceIds = $svcGroup->pluck('id')->all();

            $this->line('');
            $this->line("<fg=cyan>=== Emisor: {$emisor->name} (ID {$emisor->id}) ===</>");

            // Cobros con cuotas de estos servicios que todavía NO están bajo el emisor.
            $txs = Transaction::withTrashed()
                ->whereHas('installments.paymentPlan', fn ($q) => $q->whereIn('service_id', $serviceIds))
                ->where(fn ($q) => $q->where('owner_id', '!=', $emisor->id)->orWhereNull('owner_id'))
                ->with(['installments.paymentPlan', 'client'])
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get();

            // Solo PUROS: todas sus cuotas pertenecen a servicios de este emisor.
            $puras = $txs->filter(function ($tx) use ($serviceIds) {
                $svcs = $tx->installments->map(fn ($i) => $i->paymentPlan?->service_id)->unique();
                return $svcs->isNotEmpty() && $svcs->every(fn ($s) => in_array($s, $serviceIds, true));
            })->values();

            $mixtas = $txs->reject(fn ($tx) => $puras->contains('id', $tx->id));
            if ($mixtas->isNotEmpty()) {
                $this->warn("Hay {$mixtas->count()} cobro(s) MIXTO(s) (este servicio junto a otro en el mismo folio). NO se migran automáticamente — requieren revisión manual: " . $mixtas->pluck('folio_number')->implode(', '));
            }

            if ($puras->isEmpty()) {
                $this->info('No hay cobros puros pendientes de migrar para este emisor.');
                continue;
            }

            // Plan: folios consecutivos desde la secuencia del emisor, en orden cronológico.
            $seq = OwnerSequence::firstOrCreate(['owner_id' => $emisor->id], ['current_value' => 0]);
            $startValue = (int) $seq->current_value;
            $n = $startValue;
            $plan = [];
            foreach ($puras as $tx) {
                $n++;
                $plan[] = [
                    'tx'           => $tx,
                    'old_owner_id' => $tx->owner_id,
                    'old_folio'    => $tx->folio_number,
                    'new_folio'    => 'FOLIO-' . str_pad($n, 6, '0', STR_PAD_LEFT),
                ];
            }
            $maxAssigned = $n;

            $rows = [];
            foreach ($plan as $p) {
                $rows[] = [
                    $p['tx']->id,
                    $p['old_folio'] ?? '(s/f)',
                    $p['new_folio'],
                    $p['tx']->payment_date?->format('Y-m-d') ?? '?',
                    mb_strimwidth($p['tx']->client?->name ?? '(s/c)', 0, 26, '…'),
                    $p['tx']->trashed() ? 'cancelada' : 'activa',
                ];
            }
            $this->table(['Tx ID', 'Folio viejo', 'Folio nuevo', 'Fecha', 'Cliente', 'Estado'], $rows);

            if ($dryRun) {
                $this->info('--dry-run activo: no se modificó nada para este emisor.');
                continue;
            }

            $confirm = $this->ask("Esto modifica la BD: migra {$puras->count()} cobro(s) a {$emisor->name}. Escribí 'CONFIRMAR' para ejecutar");
            if ($confirm !== 'CONFIRMAR') {
                $this->warn('Cancelado por el usuario.');
                continue;
            }

            $logDir = storage_path('logs/traspasos');
            if (! is_dir($logDir)) {
                mkdir($logDir, 0775, true);
            }
            $logPath = $logDir . '/' . now()->format('Y-m-d-His') . '-migrar-luz-emisor' . $emisor->id . '.log';
            $logLines = [
                "Migración a emisor {$emisor->name} (ID {$emisor->id})",
                'Fecha: ' . now()->toDateTimeString(),
                '',
                'tx_id | old_owner_id | old_folio | new_folio',
            ];

            try {
                DB::transaction(function () use ($plan, $emisor, &$logLines, $maxAssigned) {
                    foreach ($plan as $p) {
                        /** @var Transaction $tx */
                        $tx = $p['tx'];
                        $logLines[] = "{$tx->id} | " . ($p['old_owner_id'] ?? 'null') . " | " . ($p['old_folio'] ?? 'null') . " | {$p['new_folio']}";
                        $tx->owner_id = $emisor->id;
                        $tx->folio_number = $p['new_folio'];
                        $tx->save();
                    }

                    $seq = OwnerSequence::lockForUpdate()->firstOrCreate(['owner_id' => $emisor->id], ['current_value' => 0]);
                    if ($seq->current_value < $maxAssigned) {
                        $seq->current_value = $maxAssigned;
                        $seq->save();
                    }
                });
            } catch (\Throwable $e) {
                $this->error('Error durante la migración: ' . $e->getMessage());
                $this->error('La transacción de BD se revirtió. Nada quedó aplicado.');
                return 1;
            }

            file_put_contents($logPath, implode(PHP_EOL, $logLines));
            $this->info("Migrados {$puras->count()} cobro(s) a {$emisor->name}.");
            $this->info("Log de auditoría: {$logPath}");
            $totalMigradas += $puras->count();
        }

        $this->line('');
        $this->info("Total de cobros migrados: {$totalMigradas}");

        return 0;
    }
}
