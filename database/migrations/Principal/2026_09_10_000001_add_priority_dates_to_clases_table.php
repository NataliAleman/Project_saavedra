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
        Schema::table('clases', function (Blueprint $table) {
            if (!Schema::hasColumn('clases', 'fecha_entrega_fundicion')) {
                $table->date('fecha_entrega_fundicion')->nullable()->after('proveedor');
            }
            if (!Schema::hasColumn('clases', 'entrega_tecamac')) {
                $table->string('entrega_tecamac')->nullable()->after('fecha_entrega_fundicion');
            }
            if (!Schema::hasColumn('clases', 'fecha_real')) {
                $table->string('fecha_real')->nullable()->after('entrega_tecamac');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('clases', 'fecha_entrega_fundicion')) {
                $columnsToDrop[] = 'fecha_entrega_fundicion';
            }
            if (Schema::hasColumn('clases', 'entrega_tecamac')) {
                $columnsToDrop[] = 'entrega_tecamac';
            }
            if (Schema::hasColumn('clases', 'fecha_real')) {
                $columnsToDrop[] = 'fecha_real';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
