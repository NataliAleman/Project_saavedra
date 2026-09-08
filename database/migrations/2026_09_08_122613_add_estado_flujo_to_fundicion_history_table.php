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
        Schema::table('fundicion_history', function (Blueprint $table) {
            $table->string('estado_flujo', 50)->nullable()->default('recibido')->after('ot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fundicion_history', function (Blueprint $table) {
            $table->dropColumn('estado_flujo');
        });
    }
};
