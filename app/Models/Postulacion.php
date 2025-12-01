<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Postulacion extends Model
{
    use HasFactory;

    // ✅ ESPECIFICA EL NOMBRE DE LA TABLA
    protected $table = 'postulaciones';

    protected $fillable = [
        'user_id',
        'vacante_id',
        'nombre_completo',
        'email', 
        'telefono',
        'carrera',
        'semestre',
        'edad',
        'habilidades',
        'experiencia',
        'motivacion',
        'cv_path',
        'solicitud_path',
        'estado',
        'mensaje_rechazo',
        'fecha_revision'
    ];

    protected $casts = [
        'fecha_revision' => 'datetime',
    ];

    // Relación con alumno
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // Relación con vacante
    public function vacante()
    {
        return $this->belongsTo(Vacante::class);
    }

    // ✅ NUEVA RELACIÓN CON SERVICIO SOCIAL
    public function servicioSocial()
    {
        return $this->hasOne(ServicioSocial::class);
    }

    // ✅ MÉTODOS PARA VERIFICAR SERVICIO SOCIAL
    public function tieneSolicitudServicioSocial()
    {
        return $this->servicioSocial()->exists();
    }

    public function servicioSocialAprobado()
    {
        return $this->servicioSocial && $this->servicioSocial->estaAprobado();
    }

    public function servicioSocialPendiente()
    {
        return $this->servicioSocial && $this->servicioSocial->estado === 'solicitado';
    }

    public function servicioSocialAceptadoEmpresa()
    {
        return $this->servicioSocial && $this->servicioSocial->estado === 'empresa_acepto';
    }

    public function servicioSocialAprobadoJefe()
    {
        return $this->servicioSocial && $this->servicioSocial->estado === 'jefe_aprobo';
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAceptadas($query)
    {
        return $query->where('estado', 'aceptado');
    }

    // ✅ NUEVO SCOPE PARA POSTULACIONES CON SERVICIO SOCIAL
    public function scopeConServicioSocial($query)
    {
        return $query->whereHas('servicioSocial');
    }
}