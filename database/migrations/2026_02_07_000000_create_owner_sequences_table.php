<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-Emisor: Permite folio secuencial independiente por cada Owner (Socio).
     * 1. Elimina el índice UNIQUE de folio_number en transactions.
     * 2. Crea la tabla owner_sequences para rastrear la numeración.
     */
    public function up(): void
    {
        // 1. Eliminar el índice unique de folio_number
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['folio_number']);
        });

        // 2. Crear tabla de secuencias por owner
        Schema::create('owner_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->unique()->constrained('owners')->onDelete('cascade');
            $table->unsignedInteger('current_value')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_sequences');

        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('folio_number');
        });
    }
};
