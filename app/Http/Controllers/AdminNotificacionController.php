<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Vacante;

class AdminNotificacionController extends Controller
{
    /**
     * Notificar a TODOS los admins sobre nueva empresa pendiente
     */
    public static function notificarNuevaEmpresaPendiente($empresa)
    {
        $admins = Usuario::where('tipo', 'admin')->get();
        
        foreach ($admins as $admin) {
            Notificacion::create([
                'user_id' => $admin->id,
                'titulo' => '🏢 Nueva Empresa Pendiente',
                'mensaje' => "La empresa '{$empresa->nombre_empresa}' está esperando aprobación. Contacto: {$empresa->email}",
                'tipo' => 'empresa_pendiente',
                'data' => [
                    'empresa_id' => $empresa->id,
                    'empresa_nombre' => $empresa->nombre_empresa,
                    'action_url' => "/admin/empresas-pendientes"
                ]
            ]);
        }
        
        \Log::info("📢 Notificación enviada a admins sobre empresa pendiente: {$empresa->nombre_empresa}");
    }

    /**
     * Notificar a TODOS los admins sobre nueva vacante pendiente
     */
    public static function notificarNuevaVacantePendiente($vacante)
    {
        $admins = Usuario::where('tipo', 'admin')->get();
        
        foreach ($admins as $admin) {
            Notificacion::create([
                'user_id' => $admin->id,
                'titulo' => '📋 Nueva Vacante Pendiente',
                'mensaje' => "La vacante '{$vacante->titulo}' de {$vacante->empresa->nombre_empresa} está esperando aprobación",
                'tipo' => 'vacante_pendiente',
                'data' => [
                    'vacante_id' => $vacante->id,
                    'vacante_titulo' => $vacante->titulo,
                    'empresa_nombre' => $vacante->empresa->nombre_empresa,
                    'action_url' => "/admin/vacantes-pendientes"
                ]
            ]);
        }
        
        \Log::info("📢 Notificación enviada a admins sobre vacante pendiente: {$vacante->titulo}");
    }

    /**
     * Notificar cuando se crea una nueva empresa (para aprobación)
     */
    public static function notificarEmpresaCreada($empresa)
    {
        self::notificarNuevaEmpresaPendiente($empresa);
    }

    /**
     * Notificar cuando se crea una nueva vacante (para aprobación)
     */
    public static function notificarVacanteCreada($vacante)
    {
        self::notificarNuevaVacantePendiente($vacante);
    }

    /**
     * Marcar notificación como procesada cuando se aprueba/rechaza una empresa
     */
    public static function marcarEmpresaProcesada($empresaId)
    {
        $updated = Notificacion::where('tipo', 'empresa_pendiente')
            ->where('data->empresa_id', $empresaId)
            ->update(['leida' => true, 'fecha_leida' => now()]);

        \Log::info("✅ Notificaciones de empresa {$empresaId} marcadas como procesadas. Actualizadas: {$updated}");
    }

    /**
     * Marcar notificación como procesada cuando se aprueba/rechaza una vacante
     */
    public static function marcarVacanteProcesada($vacanteId)
    {
        $updated = Notificacion::where('tipo', 'vacante_pendiente')
            ->where('data->vacante_id', $vacanteId)
            ->update(['leida' => true, 'fecha_leida' => now()]);

        \Log::info("✅ Notificaciones de vacante {$vacanteId} marcadas como procesadas. Actualizadas: {$updated}");
    }

    /**
     * Notificar sobre postulación nueva (OPCIONAL - para admin)
     */
    public static function notificarNuevaPostulacion($postulacion)
    {
        $admins = Usuario::where('tipo', 'admin')->get();
        
        foreach ($admins as $admin) {
            Notificacion::create([
                'user_id' => $admin->id,
                'titulo' => '📨 Nueva Postulación',
                'mensaje' => "{$postulacion->nombre_completo} se postuló a '{$postulacion->vacante->titulo}' en {$postulacion->vacante->empresa->nombre_empresa}",
                'tipo' => 'nueva_postulacion',
                'data' => [
                    'postulacion_id' => $postulacion->id,
                    'alumno_nombre' => $postulacion->nombre_completo,
                    'vacante_titulo' => $postulacion->vacante->titulo,
                    'action_url' => "/admin/postulaciones"
                ]
            ]);
        }
    }

    /**
     * Obtener notificaciones pendientes para el admin actual
     */
    public static function getNotificacionesPendientesParaAdmin()
    {
        $user = auth()->user();
        
        if ($user->tipo !== 'admin') {
            return collect();
        }

        return Notificacion::where('user_id', $user->id)
            ->where('leida', false)
            ->whereIn('tipo', ['empresa_pendiente', 'vacante_pendiente', 'nueva_postulacion'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Contador de notificaciones pendientes para admin
     */
    public static function getContadorPendientesParaAdmin()
    {
        $user = auth()->user();
        
        if ($user->tipo !== 'admin') {
            return 0;
        }

        return Notificacion::where('user_id', $user->id)
            ->where('leida', false)
            ->whereIn('tipo', ['empresa_pendiente', 'vacante_pendiente', 'nueva_postulacion'])
            ->count();
    }
}