<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->string('frecuencia', 20)->default('unico')->after('fecha_vencimiento');
            $table->unsignedInteger('plazo')->default(1)->after('frecuencia');
            $table->date('fecha_primer_pago')->nullable()->after('plazo');
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropColumn(['frecuencia', 'plazo', 'fecha_primer_pago']);
        });
    }
};
