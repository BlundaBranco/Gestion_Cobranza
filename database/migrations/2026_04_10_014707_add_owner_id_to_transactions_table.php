<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_id')->nullable()->after('client_id');
            $table->foreign('owner_id')->references('id')->on('owners')->nullOnDelete();
        });

        // Backfill: poblar owner_id desde la cadena installments → lot → owner
        DB::statement("
            UPDATE transactions t
            JOIN installment_transaction it ON it.transaction_id = t.id
            JOIN installments i ON i.id = it.installment_id
            JOIN payment_plans pp ON pp.id = i.payment_plan_id
            JOIN lots l ON l.id = pp.lot_id
            SET t.owner_id = l.owner_id
            WHERE t.owner_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }
};
