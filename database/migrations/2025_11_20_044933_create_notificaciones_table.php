<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
                // ✅ CORREGIR - referencia a usuarios
            $table->foreignId('user_id')->constrained('usuarios')->onDelete('cascade');
            
            // Información de la notificación
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('tipo'); // vacante_nueva, postulacion_aceptada, postulacion_rechazada, etc.
            $table->json('data')->nullable(); // Datos adicionales
            
            // Estado
            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_leida')->nullable();
            
            // Para notificaciones push (app móvil)
            $table->boolean('enviada_push')->default(false);
            $table->boolean('enviada_email')->default(false);
            
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'leida']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};