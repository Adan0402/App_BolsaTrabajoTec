<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Solo verificar y agregar índices si faltan
        Schema::table('registro_horas_ss', function (Blueprint $table) {
            // Verificar y agregar índices si no existen
            $indexes = [
                ['servicio_social_id', 'fecha'],
                ['fecha'],
                ['aprobado_empresa']
            ];
            
            foreach ($indexes as $index) {
                $indexName = 'registro_horas_ss_' . (is_array($index) ? implode('_', $index) : $index) . '_index';
                
                if (!Schema::hasIndex('registro_horas_ss', $indexName)) {
                    if (is_array($index)) {
                        $table->index($index);
                    } else {
                        $table->index($index);
                    }
                }
            }
        });
    }

    public function down()
    {
        // No hacer nada en el down para evitar problemas
    }
};