<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Numeración propia para servicios con emisor de folios independiente (Electrificación).
 *
 * - owners.is_billing_entity: marca emisores de folios que NO son socios reales
 *   (no se asignan como dueños de lote, pero sí emiten folios y aparecen en reportes).
 * - services.billing_owner_id: si un servicio apunta a un emisor, sus cobros toman
 *   el folio de ESE emisor en vez del socio dueño del lote.
 *
 * Crea el emisor ELECTRIFICACIÓN (secuencia desde 0 → primer folio FOLIO-000001)
 * y vincula el servicio de luz a él si existe en este entorno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->boolean('is_billing_entity')->default(false);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('billing_owner_id')->nullable()->constrained('owners')->nullOnDelete();
        });

        // Emisor ELECTRIFICACIÓN (idempotente).
        $ownerId = DB::table('owners')->where('name', 'ELECTRIFICACIÓN')->value('id');
        if (! $ownerId) {
            $ownerId = DB::table('owners')->insertGetId([
                'name' => 'ELECTRIFICACIÓN',
                'is_billing_entity' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('owners')->where('id', $ownerId)->update(['is_billing_entity' => true]);
        }

        // Secuencia del emisor arranca en 0 → el primer cobro será FOLIO-000001.
        if (! DB::table('owner_sequences')->where('owner_id', $ownerId)->exists()) {
            DB::table('owner_sequences')->insert([
                'owner_id' => $ownerId,
                'current_value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Vincular el servicio de luz a este emisor (si ya existe en este entorno).
        DB::table('services')
            ->where('name', 'like', '%lectrificaci%')
            ->update(['billing_owner_id' => $ownerId]);
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('billing_owner_id');
        });
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('is_billing_entity');
        });
        // El owner ELECTRIFICACIÓN y su secuencia se preservan a propósito:
        // borrarlos perdería la trazabilidad de los folios ya emitidos.
    }
};
