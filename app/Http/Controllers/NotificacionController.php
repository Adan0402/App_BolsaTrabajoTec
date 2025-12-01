<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    /**
     * MOSTRAR VISTA HTML
     * RUTA: /notificaciones
     */
    public function index()
    {
        return view('notificaciones.index');
    }

    /**
     * API: Obtener notificaciones del usuario (JSON)
     * RUTA: /api/notificaciones
     */
    public function getNotificaciones(Request $request)
    {
        $user = Auth::user();
        
        $notificaciones = Notificacion::where('user_id', $user->id)
            ->recientes()
            ->paginate(20);

        return response()->json([
            'notificaciones' => $notificaciones,
            'total_no_leidas' => $user->notificaciones()->noLeidas()->count()
        ]);
    }

    /**
     * Marcar notificación como leída
     * RUTA: /api/notificaciones/{notificacion}/leida
     */
    public function marcarLeida(Notificacion $notificacion)
    {
        $user = Auth::user();
        
        if ($notificacion->user_id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $notificacion->marcarComoLeida();

        return response()->json(['success' => true]);
    }

    /**
     * Marcar todas como leídas
     * RUTA: /api/notificaciones/marcar-todas-leidas
     */
    public function marcarTodasLeidas()
    {
        $user = Auth::user();
        
        Notificacion::where('user_id', $user->id)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fecha_leida' => now()
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Obtener contador de notificaciones no leídas
     * RUTA: /api/notificaciones/contador
     */
    public function contador()
    {
        $user = Auth::user();
        
        $contador = Notificacion::where('user_id', $user->id)
            ->noLeidas()
            ->count();

        return response()->json(['contador' => $contador]);
    }

    /**
     * Eliminar notificación
     * RUTA: /api/notificaciones/{notificacion}
     */
    public function destroy(Notificacion $notificacion)
    {
        $user = Auth::user();
        
        if ($notificacion->user_id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $notificacion->delete();

        return response()->json(['success' => true]);
    }
}