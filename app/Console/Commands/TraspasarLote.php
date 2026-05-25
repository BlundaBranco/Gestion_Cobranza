<?php

namespace App\Console\Commands;

use App\Models\Lot;
use App\Models\Owner;
use App\Models\OwnerSequence;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TraspasarLote extends Command
{
    protected $signature = 'lotes:traspasar
        {lot_id : ID numérico del lote a traspasar}
        {target_owner : ID numérico o nombre del socio destino}
        {--max-folio=20000 : límite superior (exclusivo) para asignar folios en el socio destino}
        {--dry-run : muestra el plan sin tocar la BD}';

    protected $description = 'Traspasa un lote a otro socio: cambia lot.owner_id, reasigna transactions y renumera folios usando huecos libres bajo --max-folio en el socio destino.';

    public function handle(): int
    {
        $lotId      = (int) $this->argument('lot_id');
        $targetArg  = (string) $this->argument('target_owner');
        $maxFolio   = (int) $this->option('max-folio');
        $dryRun     = (bool) $this->option('dry-run');

        if ($maxFolio < 1) {
            $this->error('--max-folio debe ser >= 1.');
            return 1;
        }

        $lot = Lot::with('owner')->find($lotId);
        if (!$lot) {
            $this->error("No existe lote con ID {$lotId}.");
            return 1;
        }

        $targetOwner = ctype_digit($targetArg)
            ? Owner::find((int) $targetArg)
            : Owner::where('name', $targetArg)->first();

        if (!$targetOwner) {
            $this->error("No se encontró socio destino '{$targetArg}' (probá con ID numérico).");
            return 1;
        }

        if ($lot->owner_id === $targetOwner->id) {
            $this->error("El lote ya pertenece a '{$targetOwner->name}'. No hay nada que traspasar.");
            return 1;
        }

        $currentOwner = $lot->owner;

        $this->line('');
        $this->line('<fg=cyan>=== TRASPASO DE LOTE ===</>');
        $this->line("Lote:       <fg=yellow>{$lot->identifier}</> (ID {$lot->id})");
        $this->line("Socio actual:  " . ($currentOwner?->name ?? 'sin asignar') . " (ID " . ($currentOwner?->id ?? 'null') . ")");
        $this->line("Socio destino: <fg=green>{$targetOwner->name}</> (ID {$targetOwner->id})");
        $this->line("Límite folio:  <{$maxFolio} (huecos disponibles en el destino)");
        $this->line('');

        // Transactions del lote vinculadas vía installments (tipo normal).
        $txs = Transaction::withTrashed()
            ->whereHas('installments.paymentPlan', fn($q) => $q->where('lot_id', $lot->id))
            ->with(['client'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        if ($txs->isEmpty()) {
            $this->warn('El lote no tiene transactions vinculadas a installments. Solo se reasigna lot.owner_id.');
        } else {
            $this->info("Transactions a migrar: {$txs->count()}");
        }

        // Aviso sobre extras del socio actual que NO se migran automáticamente.
        if ($currentOwner) {
            $extrasCount = Transaction::withTrashed()
                ->where('owner_id', $currentOwner->id)
                ->where('type', 'extra')
                ->count();
            if ($extrasCount > 0) {
                $this->warn("Aviso: el socio actual tiene {$extrasCount} transactions tipo 'extra'. Estas NO se migran automáticamente porque no hay relación lote-extra a nivel BD. Si alguna corresponde al lote, hay que ajustarla a mano.");
            }
        }

        // Huecos disponibles en el socio destino bajo max-folio.
        $usedNumbers = Transaction::withTrashed()
            ->where('owner_id', $targetOwner->id)
            ->whereNotNull('folio_number')
            ->where('folio_number', 'like', 'FOLIO-%')
            ->pluck('folio_number')
            ->map(fn($f) => (int) substr($f, 6))
            ->filter(fn($n) => $n >= 1 && $n < $maxFolio)
            ->unique()
            ->values()
            ->all();

        $allCandidates = range(1, $maxFolio - 1);
        $holes = array_values(array_diff($allCandidates, $usedNumbers));
        sort($holes);

        $this->info("Huecos disponibles en '{$targetOwner->name}' bajo {$maxFolio}: " . count($holes));

        if ($txs->count() > 0 && count($holes) < $txs->count()) {
            $this->error("No hay huecos suficientes: se necesitan {$txs->count()} y solo hay " . count($holes) . ".");
            $this->error('Subí --max-folio o liberá folios antes de continuar.');
            return 1;
        }

        // Plan: mapear cada tx (ordenada por payment_date) a un hueco ascendente.
        $assigned = [];
        foreach ($txs as $i => $tx) {
            $newNumber = $holes[$i];
            $newFolio  = 'FOLIO-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            $assigned[] = [
                'tx'          => $tx,
                'old_folio'   => $tx->folio_number,
                'old_owner_id'=> $tx->owner_id,
                'new_folio'   => $newFolio,
                'new_number'  => $newNumber,
            ];
        }

        if (!empty($assigned)) {
            $this->line('');
            $this->line('<fg=cyan>--- Plan de reasignación ---</>');
            $rows = [];
            foreach ($assigned as $a) {
                $tx = $a['tx'];
                $rows[] = [
                    $tx->id,
                    $a['old_folio'] ?? '(sin folio)',
                    $a['new_folio'],
                    $tx->payment_date?->format('Y-m-d') ?? '?',
                    mb_strimwidth($tx->client?->name ?? '(s/c)', 0, 28, '…'),
                    number_format((float) $tx->amount_paid, 2),
                    $tx->trashed() ? 'cancelada' : ($tx->status ?? 'active'),
                ];
            }
            $this->table(['Tx ID', 'Folio viejo', 'Folio nuevo', 'Fecha', 'Cliente', 'Monto', 'Estado'], $rows);
        }

        if ($dryRun) {
            $this->line('');
            $this->info('--dry-run activo: no se modificó nada.');
            return 0;
        }

        $this->line('');
        $confirm = $this->ask("Esto modifica la BD. Escribí 'CONFIRMAR' para ejecutar");
        if ($confirm !== 'CONFIRMAR') {
            $this->warn('Cancelado por el usuario.');
            return 1;
        }

        // Preparar log de auditoría.
        $logDir  = storage_path('logs/traspasos');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        $logPath = $logDir . '/' . now()->format('Y-m-d-His') . '-lot' . $lot->id . '.log';
        $logLines = [];
        $logLines[] = "Traspaso lote {$lot->identifier} (ID {$lot->id})";
        $logLines[] = "Fecha: " . now()->toDateTimeString();
        $logLines[] = "Socio anterior: " . ($currentOwner?->name ?? 'null') . " (ID " . ($currentOwner?->id ?? 'null') . ")";
        $logLines[] = "Socio nuevo:    {$targetOwner->name} (ID {$targetOwner->id})";
        $logLines[] = '';
        $logLines[] = 'tx_id | old_owner_id | old_folio | new_folio';

        try {
            DB::transaction(function () use ($lot, $targetOwner, $assigned, &$logLines) {
                // 1) Cambiar owner del lote
                $lot->owner_id = $targetOwner->id;
                $lot->save();

                // 2) Reasignar cada transaction (owner_id + folio_number)
                foreach ($assigned as $a) {
                    /** @var Transaction $tx */
                    $tx = $a['tx'];
                    $logLines[] = "{$tx->id} | " . ($a['old_owner_id'] ?? 'null') . " | " . ($a['old_folio'] ?? 'null') . " | {$a['new_folio']}";
                    $tx->owner_id     = $targetOwner->id;
                    $tx->folio_number = $a['new_folio'];
                    $tx->save();
                }

                // 3) Garantizar que la secuencia del socio destino nunca quede por debajo
                // del máximo folio asignado, para evitar colisiones futuras.
                $maxAssigned = collect($assigned)->max('new_number') ?? 0;
                $sequence = OwnerSequence::lockForUpdate()->firstOrCreate(
                    ['owner_id' => $targetOwner->id],
                    ['current_value' => 0]
                );
                if ($sequence->current_value < $maxAssigned) {
                    $sequence->current_value = $maxAssigned;
                    $sequence->save();
                }
            });
        } catch (\Throwable $e) {
            $this->error('Error durante el traspaso: ' . $e->getMessage());
            $this->error('La transacción de BD se revirtió. Nada quedó aplicado.');
            return 1;
        }

        file_put_contents($logPath, implode(PHP_EOL, $logLines));
        $this->line('');
        $this->info('Traspaso completado.');
        $this->info("Log de auditoría: {$logPath}");

        return 0;
    }
}
