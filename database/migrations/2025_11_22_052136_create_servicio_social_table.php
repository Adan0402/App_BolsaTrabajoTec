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
        // TABLA PRINCIPAL DE SERVICIO SOCIAL
        Schema::create('servicio_social', function (Blueprint $table) {
            $table->id();
            
            // RELACIONES PRINCIPALES
            $table->foreignId('postulacion_id')->constrained('postulaciones')->onDelete('cascade');
            $table->foreignId('alumno_id')->constrained('usuarios')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('vacante_id')->constrained('vacantes')->onDelete('cascade');
            $table->foreignId('jefe_ss_id')->nullable()->constrained('usuarios')->onDelete('set null');
            
            // INFORMACIÓN DEL ALUMNO
            $table->string('carrera');
            $table->string('semestre');
            $table->string('numero_control');
            
            // PERIODO DEL SERVICIO SOCIAL
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin_estimada')->nullable();
            $table->date('fecha_fin_real')->nullable();
            
            // CONTROL DE HORAS
            $table->integer('horas_requeridas')->default(480);
            $table->integer('horas_completadas')->default(0);
            $table->integer('horas_pendientes')->default(480);
            
            // ESTADOS DEL PROCESO
            $table->enum('estado', [
                'solicitado',       // Alumno solicitó
                'empresa_acepto',   // Empresa aceptó
                'jefe_aprobo',      // Jefe SS aprobó
                'en_proceso',       // En ejecución
                'completado',       // Terminado exitosamente
                'rechazado'         // Rechazado
            ])->default('solicitado');
            
            // APROBACIONES
            $table->boolean('empresa_acepta')->default(false);
            $table->boolean('jefe_ss_aprueba')->default(false);
            $table->text('observaciones_empresa')->nullable();
            $table->text('observaciones_jefe')->nullable();
            
            // SUPERVISIÓN EN LA EMPRESA
            $table->string('supervisor_empresa')->nullable();
            $table->string('email_supervisor')->nullable();
            $table->string('telefono_supervisor')->nullable();
            
            // INFORMACIÓN DEL PROYECTO
            $table->string('nombre_proyecto')->nullable();
            $table->text('actividades_principales')->nullable();
            
            // FECHAS IMPORTANTES
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_empresa_acepto')->nullable();
            $table->timestamp('fecha_jefe_aprobo')->nullable();
            $table->timestamp('fecha_inicio_proceso')->nullable();
            $table->timestamp('fecha_finalizacion')->nullable();
            
            $table->timestamps();
            
            // ÍNDICES PARA MEJOR PERFORMANCE
            $table->index(['alumno_id', 'estado']);
            $table->index(['empresa_id', 'estado']);
            $table->index('estado');
        });

        // TABLA PARA REGISTRO DIARIO DE HORAS
        Schema::create('registro_horas_ss', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_social_id')->constrained('servicio_social')->onDelete('cascade');
            $table->date('fecha');
            $table->integer('horas_trabajadas');
            $table->text('actividades_realizadas');
            $table->text('evidencias')->nullable(); // Para guardar URLs de archivos
            $table->boolean('aprobado_empresa')->default(false);
            $table->boolean('aprobado_jefe')->default(false);
            $table->timestamps();
            
            // ÍNDICES
            $table->index(['servicio_social_id', 'fecha']);
            $table->index('fecha');
        });

        // TABLA PARA JEFES DE SERVICIO SOCIAL (SI NO EXISTE)
        if (!Schema::hasTable('jefes_servicio_social')) {
            Schema::create('jefes_servicio_social', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
                $table->string('departamento');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_horas_ss');
        Schema::dropIfExists('servicio_social');
        Schema::dropIfExists('jefes_servicio_social');
    }
};