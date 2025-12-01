<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('rol_especifico', [
                'servicio_social',
                'vinculacion', 
                'direccion',
                'general'
            ])->nullable()->after('tipo');
        });
    }

    public function down()
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('rol_especifico');
        });
    }
};