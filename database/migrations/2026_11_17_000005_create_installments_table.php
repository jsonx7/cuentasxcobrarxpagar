<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_id')->constrained('receivables')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 12, 2);
            $table->decimal('interes', 12, 2)->default(0);
            $table->decimal('capital', 12, 2);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->decimal('abono_extra', 12, 2)->default(0);
            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->decimal('mora_pagada', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['receivable_id', 'numero']);
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
