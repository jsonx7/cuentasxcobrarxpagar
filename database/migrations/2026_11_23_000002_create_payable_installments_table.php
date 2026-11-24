<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payable_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payable_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->date('fecha_vencimiento');
            $table->boolean('pagada')->default(false);
            $table->timestamps();

            $table->unique(['payable_id', 'numero']);
            $table->index(['payable_id', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payable_installments');
    }
};
