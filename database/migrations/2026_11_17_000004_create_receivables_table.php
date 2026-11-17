<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->decimal('tasa_interes_anual', 6, 2)->default(0);
            $table->unsignedInteger('plazo');
            $table->string('frecuencia', 20);
            $table->date('fecha_desembolso');
            $table->date('fecha_primer_pago');
            $table->decimal('cuota', 12, 2);
            $table->decimal('interes_total', 12, 2)->default(0);
            $table->decimal('total_a_pagar', 12, 2);
            $table->decimal('tasa_mora_mensual', 5, 2)->default(5);
            $table->string('estado', 20)->default('activo');
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('estado');
            $table->index('frecuencia');
            $table->index('fecha_desembolso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
