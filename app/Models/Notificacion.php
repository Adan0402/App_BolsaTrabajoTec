<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;
    
    protected $table = 'notificaciones';

    protected $fillable = [
        'user_id',
        'titulo',
        'mensaje',
        'tipo',
        'data',
        'leida',
        'fecha_leida',
        'enviada_push',
        'enviada_email'
    ];

    protected $casts = [
        'data' => 'array',
        'fecha_leida' => 'datetime',
    ];

    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(Usuario::class);
    }

    // Scopes
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeRecientes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Marcar como leída
    public function marcarComoLeida()
    {
        $this->update([
            'leida' => true,
            'fecha_leida' => now()
        ]);
    }

    // Métodos estáticos para crear notificaciones
    public static function crearVacanteNueva($user, $vacante)
    {
        return self::create([
            'user_id' => $user->id,
            'titulo' => '🎯 Nueva Vacante Disponible',
            'mensaje' => "Hay una nueva vacante para: {$vacante->titulo} en {$vacante->empresa->nombre_empresa}",
            'tipo' => 'vacante_nueva',
            'data' => [
                'vacante_id' => $vacante->id,
                'empresa_id' => $vacante->empresa_id,
                'action_url' => "/alumno/vacantes/{$vacante->id}/postular"
            ]
        ]);
    }

    public static function crearPostulacionAceptada($user, $postulacion)
    {
        return self::create([
            'user_id' => $user->id,
            'titulo' => '✅ ¡Postulación Aceptada!',
            'mensaje' => "Tu postulación para '{$postulacion->vacante->titulo}' ha sido aceptada por {$postulacion->vacante->empresa->nombre_empresa}",
            'tipo' => 'postulacion_aceptada',
            'data' => [
                'postulacion_id' => $postulacion->id,
                'vacante_id' => $postulacion->vacante_id,
                'empresa_id' => $postulacion->vacante->empresa_id,
                'action_url' => "/alumno/mis-postulaciones"
            ]
        ]);
    }

    public static function crearPostulacionRechazada($user, $postulacion)
    {
        return self::create([
            'user_id' => $user->id,
            'titulo' => '❌ Postulación Rechazada',
            'mensaje' => "Tu postulación para '{$postulacion->vacante->titulo}' no fue aceptada. Motivo: {$postulacion->mensaje_rechazo}",
            'tipo' => 'postulacion_rechazada',
            'data' => [
                'postulacion_id' => $postulacion->id,
                'vacante_id' => $postulacion->vacante_id,
                'empresa_id' => $postulacion->vacante->empresa_id,
                'action_url' => "/alumno/mis-postulaciones"
            ]
        ]);
    }

    public static function crearNuevaPostulacionEmpresa($user, $postulacion)
    {
        return self::create([
            'user_id' => $user->id,
            'titulo' => '📨 Nueva Postulación Recibida',
            'mensaje' => "{$postulacion->nombre_completo} se ha postulado a tu vacante: {$postulacion->vacante->titulo}",
            'tipo' => 'nueva_postulacion_empresa',
            'data' => [
                'postulacion_id' => $postulacion->id,
                'vacante_id' => $postulacion->vacante_id,
                'alumno_nombre' => $postulacion->nombre_completo,
                'action_url' => "/empresa/postulaciones/{$postulacion->id}"
            ]
        ]);
    }

    // ✅ MÉTODOS PARA EL ADMINCONTROLLER (Admin → Empresas)

    public static function nuevaVacanteAprobada($vacante)
    {
        return self::create([
            'user_id' => $vacante->empresa->user_id,
            'titulo' => '✅ Vacante Aprobada',
            'mensaje' => "Tu vacante '{$vacante->titulo}' ha sido aprobada y ya está visible para los alumnos.",
            'tipo' => 'vacante_aprobada',
            'data' => [
                'vacante_id' => $vacante->id,
                'action_url' => "/empresa/vacantes"
            ]
        ]);
    }

    public static function nuevaVacanteRechazada($vacante, $motivo = '')
    {
        return self::create([
            'user_id' => $vacante->empresa->user_id,
            'titulo' => '❌ Vacante Rechazada',
            'mensaje' => "Tu vacante '{$vacante->titulo}' fue rechazada. Motivo: {$motivo}",
            'tipo' => 'vacante_rechazada',
            'data' => [
                'vacante_id' => $vacante->id,
                'action_url' => "/empresa/vacantes"
            ]
        ]);
    }

    public static function nuevaEmpresaAprobada($empresa)
    {
        return self::create([
            'user_id' => $empresa->user_id,
            'titulo' => '✅ Empresa Aprobada',
            'mensaje' => "¡Felicidades! Tu empresa '{$empresa->nombre_empresa}' ha sido aprobada y ya puedes publicar vacantes.",
            'tipo' => 'empresa_aprobada',
            'data' => [
                'empresa_id' => $empresa->id,
                'action_url' => "/dashboard"
            ]
        ]);
    }

    public static function nuevaEmpresaRechazada($empresa, $motivo = '')
    {
        return self::create([
            'user_id' => $empresa->user_id,
            'titulo' => '❌ Empresa Rechazada',
            'mensaje' => "Tu solicitud de empresa '{$empresa->nombre_empresa}' fue rechazada. Motivo: {$motivo}",
            'tipo' => 'empresa_rechazada',
            'data' => [
                'empresa_id' => $empresa->id,
                'action_url' => "/dashboard"
            ]
        ]);
    }

    // ✅ NUEVOS MÉTODOS PARA NOTIFICACIONES AL ADMIN (Empresas → Admin)

    public static function nuevaEmpresaRegistrada($empresa)
    {
        $admin = Usuario::where('tipo', 'admin')->first();
        
        if ($admin) {
            return self::create([
                'user_id' => $admin->id,
                'titulo' => '🏢 Nueva Empresa Registrada',
                'mensaje' => "La empresa '{$empresa->nombre_empresa}' se ha registrado y está pendiente de aprobación.",
                'tipo' => 'nueva_empresa',
                'data' => [
                    'empresa_id' => $empresa->id,
                    'action_url' => "/admin/empresas-pendientes"
                ]
            ]);
        }
    }

    public static function nuevaVacanteCreada($vacante, $adminId)
    {
        return self::create([
            'user_id' => $adminId,
            'titulo' => '📋 Nueva Vacante Pendiente',
            'mensaje' => "La empresa {$vacante->empresa->nombre_empresa} ha publicado una nueva vacante: {$vacante->titulo}",
            'tipo' => 'nueva_vacante',
            'data' => [
                'vacante_id' => $vacante->id,
                'empresa_id' => $vacante->empresa_id,
                'action_url' => '/admin/vacantes-pendientes'
            ]
        ]);
    }
}