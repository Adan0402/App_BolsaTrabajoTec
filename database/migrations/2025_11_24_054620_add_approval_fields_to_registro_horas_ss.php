<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('registro_horas_ss', function (Blueprint $table) {
            if (!Schema::hasColumn('registro_horas_ss', 'observaciones_empresa')) {
                $table->text('observaciones_empresa')->nullable()->after('evidencias');
            }
            
            if (!Schema::hasColumn('registro_horas_ss', 'observaciones_jefe')) {
                $table->text('observaciones_jefe')->nullable()->after('observaciones_empresa');
            }
            
            if (!Schema::hasColumn('registro_horas_ss', 'fecha_aprobacion_empresa')) {
                $table->timestamp('fecha_aprobacion_empresa')->nullable()->after('observaciones_jefe');
            }
            
            if (!Schema::hasColumn('registro_horas_ss', 'fecha_aprobacion_jefe')) {
                $table->timestamp('fecha_aprobacion_jefe')->nullable()->after('fecha_aprobacion_empresa');
            }
        });
    }

    public function down()
    {
        Schema::table('registro_horas_ss', function (Blueprint $table) {
            $table->dropColumn([
                'observaciones_empresa',
                'observaciones_jefe',
                'fecha_aprobacion_empresa', 
                'fecha_aprobacion_jefe'
            ]);
        });
    }
};