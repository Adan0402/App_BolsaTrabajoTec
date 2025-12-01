<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('usuarios', function (Blueprint $table) {
            // ✅ CAMPOS BÁSICOS PARA TODOS
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('tipo', ['alumno', 'egresado', 'empresa', 'admin'])->default('alumno');
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
            
            // 📞 CAMPOS OPCIONALES
            $table->string('telefono')->nullable();
            $table->text('direccion')->nullable();
            $table->string('foto_perfil')->nullable();
            
            // 🎓 CAMPOS PARA ALUMNOS/EGRESADOS
            $table->string('numero_control')->unique()->nullable();
            $table->string('carrera')->nullable();
            $table->integer('semestre')->nullable();
            $table->decimal('promedio', 3, 2)->nullable();
            $table->string('cv_path')->nullable();
            $table->json('habilidades')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuarios');
    }
};
