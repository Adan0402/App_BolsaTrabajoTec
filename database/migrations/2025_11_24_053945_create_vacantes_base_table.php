<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vacantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('ubicacion');
            $table->enum('tipo_contrato', ['tiempo_completo', 'medio_tiempo', 'practicas', 'servicio_social']);
            $table->decimal('salario', 10, 2)->nullable();
            $table->text('requisitos');
            $table->text('beneficios')->nullable();
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->string('motivo_rechazo')->nullable();
            $table->timestamps();
            
            $table->index(['empresa_id', 'estado']);
            $table->index('tipo_contrato');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vacantes');
    }
};