<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage; // ✅ AGREGAR ESTO AL INICIO

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'name', 'email', 'password', 'tipo', 'activo',
        'telefono', 'direccion', 'foto_perfil', // ✅ ESTE CAMPO YA EXISTE
        'numero_control', 'carrera', 'semestre', 'promedio', 'cv_path', 'habilidades'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'habilidades' => 'array',
        'promedio' => 'decimal:2',
    ];

    // ✅ AGREGAR ESTE MÉTODO ACCESOR (NUEVO)
    public function getFotoPerfilUrlAttribute()
    {
        // Verificar si existe el campo foto_perfil
        if ($this->foto_perfil) {
            // Si la foto está en storage
            if (Storage::exists($this->foto_perfil)) {
                return Storage::url($this->foto_perfil);
            }
            // Si la foto es una URL completa
            if (filter_var($this->foto_perfil, FILTER_VALIDATE_URL)) {
                return $this->foto_perfil;
            }
        }
        
        // Fallback: Avatar con iniciales del nombre
        $nombre = $this->name ?? 'Usuario';
        $nombreCodificado = urlencode($nombre);
        
        // Generar avatar con colores ITSZN
        return "https://ui-avatars.com/api/?name={$nombreCodificado}&background=1B396A&color=fff&size=200&bold=true&font-size=0.5";
    }

    // ✅ AGREGAR ESTE OTRO MÉTODO PARA VISTAS PEQUEÑAS
    public function getFotoPerfilMiniaturaAttribute()
    {
        if ($this->foto_perfil) {
            if (Storage::exists($this->foto_perfil)) {
                return Storage::url($this->foto_perfil);
            }
            if (filter_var($this->foto_perfil, FILTER_VALIDATE_URL)) {
                return $this->foto_perfil;
            }
        }
        
        $nombre = $this->name ?? 'U';
        $inicial = strtoupper(substr($nombre, 0, 1));
        $nombreCodificado = urlencode($inicial);
        
        return "https://ui-avatars.com/api/?name={$nombreCodificado}&background=1B396A&color=fff&size=64&bold=true";
    }

    // ... resto de tus métodos existentes ...
    public function empresa()
    {
        return $this->hasOne(Empresa::class, 'user_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'user_id');
    }

    public function postulaciones()
    {
        return $this->hasMany(Postulacion::class, 'user_id');
    }

    public function esAlumno()
    {
        return $this->tipo === 'alumno';
    }

    public function esEmpresa()
    {
        return $this->tipo === 'empresa';
    }

    public function esAdmin()
    {
        return $this->tipo === 'admin';
    }
}