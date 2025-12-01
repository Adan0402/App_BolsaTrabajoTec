<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('servicio_social', function (Blueprint $table) {
            // Cambiar el orden de los estados
            $table->enum('estado', [
                'solicitado',       // Alumno solicitó
                'jefe_aprobo',      // Jefe SS aprobó PRIMERO
                'empresa_acepto',   // Empresa acepta DESPUÉS
                'en_proceso',       // En ejecución
                'completado',       // Terminado
                'rechazado'         // Rechazado
            ])->default('solicitado')->change();
        });
    }

    public function down()
    {
        Schema::table('servicio_social', function (Blueprint $table) {
            $table->enum('estado', [
                'solicitado',
                'empresa_acepto', 
                'jefe_aprobo',
                'en_proceso',
                'completado',
                'rechazado'
            ])->default('solicitado')->change();
        });
    }
};