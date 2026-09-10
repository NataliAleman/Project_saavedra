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
        Schema::table('users', function (Blueprint $table) {
            $table->string('turno', 50)->nullable()->after('perfil');
            $table->string('planta', 50)->nullable()->after('turno');
            $table->string('area', 100)->nullable()->after('planta');
            $table->string('puesto', 100)->nullable()->after('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['turno', 'planta', 'area', 'puesto']);
        });
    }
};
