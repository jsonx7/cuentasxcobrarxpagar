<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('payable_installments');

        Schema::table('payables', function (Blueprint $table) {
            $table->dropColumn(['frecuencia', 'plazo']);
        });

        Schema::table('payable_payments', function (Blueprint $table) {
            $table->boolean('es_prima')->default(false)->after('concepto');
        });
    }

    public function down(): void
    {
        Schema::table('payable_payments', function (Blueprint $table) {
            $table->dropColumn('es_prima');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->string('frecuencia', 20)->default('unico')->after('fecha_vencimiento');
            $table->unsignedInteger('plazo')->default(1)->after('frecuencia');
        });

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
};
