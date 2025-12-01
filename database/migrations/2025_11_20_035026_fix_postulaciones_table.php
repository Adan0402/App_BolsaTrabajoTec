<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar si existe
        Schema::dropIfExists('postulaciones');
        
        // Crear correctamente
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vacante_id')->constrained()->onDelete('cascade');
            
            // Información del alumno
            $table->string('nombre_completo');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->string('carrera');
            $table->integer('semestre');
            $table->integer('edad');
            $table->text('habilidades')->nullable();
            $table->text('experiencia')->nullable();
            $table->text('motivacion')->nullable();
            
            // Archivos
            $table->string('cv_path')->nullable();
            $table->string('solicitud_path')->nullable();
            
            // Estado
            $table->enum('estado', ['pendiente', 'revisado', 'aceptado', 'rechazado'])->default('pendiente');
            $table->text('mensaje_rechazo')->nullable();
            $table->timestamp('fecha_revision')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->unique(['user_id', 'vacante_id']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulaciones');
    }
};
