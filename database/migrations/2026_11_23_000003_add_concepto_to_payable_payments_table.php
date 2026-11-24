<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payable_payments', function (Blueprint $table) {
            $table->string('concepto', 160)->nullable()->after('monto');
        });
    }

    public function down(): void
    {
        Schema::table('payable_payments', function (Blueprint $table) {
            $table->dropColumn('concepto');
        });
    }
};
