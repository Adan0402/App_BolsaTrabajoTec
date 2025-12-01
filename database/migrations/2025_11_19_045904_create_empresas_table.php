<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('nombre_empresa');
            $table->string('tipo_negocio'); // restaurante, tienda, servicios, etc.
            $table->string('tamano_empresa'); // micro, pequena, mediana, grande
            $table->string('rfc')->nullable();
            $table->text('direccion')->nullable();
            $table->string('telefono_contacto');
            $table->string('correo_contacto');
            $table->string('representante_legal');
            $table->string('puesto_representante');
            $table->string('pagina_web')->nullable();
            $table->text('descripcion_empresa')->nullable();
            
            // SISTEMA DE APROBACIÓN
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios');
            $table->timestamp('fecha_revision')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
};
