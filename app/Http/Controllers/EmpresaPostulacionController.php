<?php

namespace App\Http\Controllers;

use App\Models\Postulacion;
use App\Models\Vacante;
use App\Models\Notificacion; // ✅ AGREGAR ESTA IMPORTACIÓN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmpresaPostulacionController extends Controller
{
    /**
     * Mostrar todas las postulaciones de la empresa
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return redirect('/dashboard')->with('error', 'Solo las empresas pueden ver postulaciones.');
        }

        // Obtener todas las postulaciones de las vacantes de esta empresa
        $postulaciones = Postulacion::with(['vacante', 'user'])
            ->whereIn('vacante_id', $user->empresa->vacantes->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->get();

        // Estadísticas
        $estadisticas = [
            'total' => $postulaciones->count(),
            'pendientes' => $postulaciones->where('estado', 'pendiente')->count(),
            'aceptadas' => $postulaciones->where('estado', 'aceptado')->count(),
            'rechazadas' => $postulaciones->where('estado', 'rechazado')->count(),
        ];

        return view('empresas.postulaciones', compact('user', 'postulaciones', 'estadisticas'));
    }

    /**
     * Mostrar detalle de una postulación específica
     */
    public function show(Postulacion $postulacion)
    {
        $user = Auth::user();
        
        // Verificar que la postulación pertenece a una vacante de la empresa
        if ($user->tipo !== 'empresa' || !$user->empresa || 
            $postulacion->vacante->empresa_id !== $user->empresa->id) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para ver esta postulación.');
        }

        return view('empresas.postulacion-detalle', compact('user', 'postulacion'));
    }

    /**
     * Aprobar una postulación
     */
    public function aprobar(Postulacion $postulacion)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa || 
            $postulacion->vacante->empresa_id !== $user->empresa->id) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para gestionar esta postulación.');
        }

        $postulacion->update([
            'estado' => 'aceptado',
            'fecha_revision' => now(),
            'mensaje_rechazo' => null,
        ]);

        // ✅ NUEVO: NOTIFICAR AL ALUMNO SOBRE POSTULACIÓN ACEPTADA
        Notificacion::crearPostulacionAceptada($postulacion->user, $postulacion);

        return redirect()->route('empresa.postulaciones')
            ->with('success', "✅ Postulación de {$postulacion->nombre_completo} aceptada correctamente.");
    }

    /**
     * Rechazar una postulación
     */
    public function rechazar(Request $request, Postulacion $postulacion)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa || 
            $postulacion->vacante->empresa_id !== $user->empresa->id) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para gestionar esta postulación.');
        }

        $request->validate([
            'mensaje_rechazo' => 'required|string|min:10|max:500'
        ]);

        $postulacion->update([
            'estado' => 'rechazado',
            'fecha_revision' => now(),
            'mensaje_rechazo' => $request->mensaje_rechazo,
        ]);

        // ✅ NUEVO: NOTIFICAR AL ALUMNO SOBRE POSTULACIÓN RECHAZADA
        Notificacion::crearPostulacionRechazada($postulacion->user, $postulacion);

        return redirect()->route('empresa.postulaciones')
            ->with('success', "❌ Postulación de {$postulacion->nombre_completo} rechazada correctamente.");
    }

    /**
     * Descargar CV desde el panel de empresa
     */
    public function descargarCv(Postulacion $postulacion)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa || 
            $postulacion->vacante->empresa_id !== $user->empresa->id) {
            return abort(403, 'No tienes permisos para descargar este archivo.');
        }

        if (!$postulacion->cv_path) {
            return back()->with('error', 'El archivo CV no existe.');
        }

        return Storage::disk('public')->download($postulacion->cv_path);
    }

    /**
     * Descargar solicitud desde el panel de empresa
     */
    public function descargarSolicitud(Postulacion $postulacion)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa || 
            $postulacion->vacante->empresa_id !== $user->empresa->id) {
            return abort(403, 'No tienes permisos para descargar este archivo.');
        }

        if (!$postulacion->solicitud_path) {
            return back()->with('error', 'El archivo de solicitud no existe.');
        }

        return Storage::disk('public')->download($postulacion->solicitud_path);
    }

    /**
     * Filtrar postulaciones por vacante
     */
    public function porVacante(Vacante $vacante)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa || $vacante->empresa_id !== $user->empresa->id) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para ver estas postulaciones.');
        }

        $postulaciones = Postulacion::with(['user'])
            ->where('vacante_id', $vacante->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('empresas.postulaciones-vacante', compact('user', 'postulaciones', 'vacante'));
    }
}