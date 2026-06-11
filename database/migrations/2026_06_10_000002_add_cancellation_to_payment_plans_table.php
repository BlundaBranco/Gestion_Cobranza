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
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->string('status')->default('active')->after('start_date');
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->onDelete('set null');
            // Snapshot del cliente titular al cancelar: al revender el lote,
            // lots.client_id pasa al cliente nuevo y se pierde quién era.
            $table->foreignId('cancelled_client_id')->nullable()->after('cancelled_by')->constrained('clients')->onDelete('set null');
            $table->text('cancellation_notes')->nullable()->after('cancelled_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropConstrainedForeignId('cancelled_client_id');
            $table->dropColumn(['status', 'cancelled_at', 'cancellation_notes']);
        });
    }
};
