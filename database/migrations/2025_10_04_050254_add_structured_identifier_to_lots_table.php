<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->string('block_number')->after('client_id'); // Manzana
            $table->string('lot_number')->after('block_number'); // Lote
            
            // Hacer el identificador original nullable temporalmente para la migración
            $table->string('identifier')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            //
        });
    }
};
