<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('mora_base', 14, 4)->nullable();
            $table->date('mora_fecha_hasta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn(['mora_base', 'mora_fecha_hasta']);
        });
    }
};
