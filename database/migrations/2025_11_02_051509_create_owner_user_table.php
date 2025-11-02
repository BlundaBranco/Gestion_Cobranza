<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_user', function (Blueprint $table) {
            $table->primary(['owner_id', 'user_id']); // Clave primaria compuesta
            
            $table->foreignId('owner_id')->constrained('owners')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_user');
    }
};