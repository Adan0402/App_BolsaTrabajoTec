<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacantes', function (Blueprint $table) {
            // Solo agregar empresa_id si no existe
            if (!Schema::hasColumn('vacantes', 'empresa_id')) {
                $table->foreignId('empresa_id')->constrained()->onDelete('cascade');
            }
            
            // Agregar titulo si no existe
            if (!Schema::hasColumn('vacantes', 'titulo')) {
                $table->string('titulo');
            }
            
            // Agregar descripcion si no existe
            if (!Schema::hasColumn('vacantes', 'descripcion')) {
                $table->text('descripcion');
            }
            
            // Agregar requisitos si no existe
            if (!Schema::hasColumn('vacantes', 'requisitos')) {
                $table->text('requisitos');
            }
            
            // Agregar beneficios si no existe
            if (!Schema::hasColumn('vacantes', 'beneficios')) {
                $table->text('beneficios')->nullable();
            }
            
            // Agregar tipo_contrato si no existe
            if (!Schema::hasColumn('vacantes', 'tipo_contrato')) {
                $table->enum('tipo_contrato', ['tiempo_completo', 'medio_tiempo', 'practicas', 'freelance', 'proyecto']);
            }
            
            // Agregar salario_min si no existe
            if (!Schema::hasColumn('vacantes', 'salario_min')) {
                $table->decimal('salario_min', 10, 2)->nullable();
            }
            
            // Agregar salario_max si no existe
            if (!Schema::hasColumn('vacantes', 'salario_max')) {
                $table->decimal('salario_max', 10, 2)->nullable();
            }
            
            // Agregar salario_mostrar si no existe
            if (!Schema::hasColumn('vacantes', 'salario_mostrar')) {
                $table->boolean('salario_mostrar')->default(true);
            }
            
            // Agregar ubicacion si no existe
            if (!Schema::hasColumn('vacantes', 'ubicacion')) {
                $table->string('ubicacion');
            }
            
            // Agregar modalidad si no existe
            if (!Schema::hasColumn('vacantes', 'modalidad')) {
                $table->enum('modalidad', ['presencial', 'remoto', 'hibrido']);
            }
            
            // Agregar nivel_experiencia si no existe
            if (!Schema::hasColumn('vacantes', 'nivel_experiencia')) {
                $table->enum('nivel_experiencia', ['sin_experiencia', 'junior', 'mid', 'senior']);
            }
            
            // Agregar vacantes_disponibles si no existe
            if (!Schema::hasColumn('vacantes', 'vacantes_disponibles')) {
                $table->integer('vacantes_disponibles')->default(1);
            }
            
            // Agregar fecha_limite si no existe
            if (!Schema::hasColumn('vacantes', 'fecha_limite')) {
                $table->date('fecha_limite');
            }
            
            // Agregar estado si no existe
            if (!Schema::hasColumn('vacantes', 'estado')) {
                $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cerrada'])->default('pendiente');
            }
            
            // Agregar motivo_rechazo si no existe
            if (!Schema::hasColumn('vacantes', 'motivo_rechazo')) {
                $table->text('motivo_rechazo')->nullable();
            }
            
            // Agregar activa si no existe
            if (!Schema::hasColumn('vacantes', 'activa')) {
                $table->boolean('activa')->default(true);
            }
        });
    }

    public function down(): void
    {
        // No hacemos rollback para no perder las columnas agregadas
    }
};
