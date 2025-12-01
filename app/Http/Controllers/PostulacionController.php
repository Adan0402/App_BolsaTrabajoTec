<?php

namespace App\Http\Controllers;

use App\Models\Postulacion;
use App\Models\Vacante;
use App\Models\Notificacion; // ✅ AGREGAR ESTA IMPORTACIÓN
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostulacionController extends Controller
{
    /**
     * Mostrar vacantes disponibles para alumnos
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'alumno') {
            return redirect('/dashboard')->with('error', 'Solo los alumnos pueden ver vacantes.');
        }

        $vacantes = Vacante::with('empresa')
            ->where('estado', 'aprobada')
            ->where('activa', true)
            ->where('fecha_limite', '>=', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('alumno.vacantes-disponibles', compact('user', 'vacantes'));
    }

    /**
     * Mostrar formulario de postulación
     */
    public function create(Vacante $vacante)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'alumno') {
            return redirect('/dashboard')->with('error', 'Solo los alumnos pueden postularse.');
        }

        // Verificar que la vacante esté disponible
        if ($vacante->estado !== 'aprobada' || !$vacante->activa || $vacante->fecha_limite < now()) {
            return redirect()->route('alumno.vacantes')
                ->with('error', 'Esta vacante ya no está disponible.');
        }

        // Verificar si ya se postuló
        $yaPostulado = Postulacion::where('user_id', $user->id)
            ->where('vacante_id', $vacante->id)
            ->exists();

        if ($yaPostulado) {
            return redirect()->route('alumno.vacantes')
                ->with('error', 'Ya te has postulado a esta vacante.');
        }

        return view('alumno.postular', compact('user', 'vacante'));
    }

    /**
     * Procesar postulación
     */
    public function store(Request $request, Vacante $vacante)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'alumno') {
            return redirect('/dashboard')->with('error', 'Solo los alumnos pueden postularse.');
        }

        // Validaciones de seguridad
        if ($vacante->estado !== 'aprobada' || !$vacante->activa || $vacante->fecha_limite < now()) {
            return redirect()->route('alumno.vacantes')
                ->with('error', 'Esta vacante ya no está disponible.');
        }

        // Verificar si ya se postuló
        $yaPostulado = Postulacion::where('user_id', $user->id)
            ->where('vacante_id', $vacante->id)
            ->exists();

        if ($yaPostulado) {
            return redirect()->route('alumno.vacantes')
                ->with('error', 'Ya te has postulado a esta vacante.');
        }

        // Validar datos
        $validated = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email',
            'telefono' => 'required|string|max:20',
            'carrera' => 'required|string|max:255',
            'semestre' => 'required|integer|min:1|max:12',
            'edad' => 'required|integer|min:16|max:80',
            'habilidades' => 'required|string|min:20',
            'experiencia' => 'nullable|string|min:10',
            'motivacion' => 'required|string|min:30',
            'cv' => 'required|file|mimes:pdf|max:2048', // 2MB max
            'solicitud' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            // Guardar archivos
            $cvPath = $request->file('cv')->store('cvs', 'public');
            $solicitudPath = $request->hasFile('solicitud') 
                ? $request->file('solicitud')->store('solicitudes', 'public') 
                : null;

            // Crear postulación
            $postulacion = Postulacion::create([
                'user_id' => $user->id,
                'vacante_id' => $vacante->id,
                'nombre_completo' => $validated['nombre_completo'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'carrera' => $validated['carrera'],
                'semestre' => $validated['semestre'],
                'edad' => $validated['edad'],
                'habilidades' => $validated['habilidades'],
                'experiencia' => $validated['experiencia'],
                'motivacion' => $validated['motivacion'],
                'cv_path' => $cvPath,
                'solicitud_path' => $solicitudPath,
                'estado' => 'pendiente',
            ]);

            // ✅ NUEVO: NOTIFICAR A LA EMPRESA SOBRE LA NUEVA POSTULACIÓN
            $empresaUser = $vacante->empresa->user;
            Notificacion::crearNuevaPostulacionEmpresa($empresaUser, $postulacion);

            return redirect()->route('alumno.mis-postulaciones')
                ->with('success', '✅ ¡Postulación enviada correctamente! La empresa revisará tu solicitud.');

        } catch (\Exception $e) {
            // Eliminar archivos si hay error
            if (isset($cvPath)) {
                Storage::disk('public')->delete($cvPath);
            }
            if (isset($solicitudPath)) {
                Storage::disk('public')->delete($solicitudPath);
            }

            return back()->with('error', 'Error al enviar la postulación: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar postulaciones del alumno
     */
    public function misPostulaciones()
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'alumno') {
            return redirect('/dashboard')->with('error', 'Solo los alumnos pueden ver sus postulaciones.');
        }

        $postulaciones = Postulacion::with('vacante.empresa')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('alumno.mis-postulaciones', compact('user', 'postulaciones'));
    }

    /**
     * Descargar CV
     */
    public function descargarCv(Postulacion $postulacion)
    {
        $user = Auth::user();
        
        // Verificar permisos (solo el alumno dueño o admin/empresa relacionada)
        if ($user->id !== $postulacion->user_id && $user->tipo !== 'admin' && 
            (!$user->empresa || $user->empresa->id !== $postulacion->vacante->empresa_id)) {
            return abort(403, 'No tienes permisos para descargar este archivo.');
        }

        if (!$postulacion->cv_path) {
            return back()->with('error', 'El archivo no existe.');
        }

        return Storage::disk('public')->download($postulacion->cv_path);
    }

    /**
     * Descargar solicitud
     */
    public function descargarSolicitud(Postulacion $postulacion)
    {
        $user = Auth::user();
        
        if ($user->id !== $postulacion->user_id && $user->tipo !== 'admin' && 
            (!$user->empresa || $user->empresa->id !== $postulacion->vacante->empresa_id)) {
            return abort(403, 'No tienes permisos para descargar este archivo.');
        }

        if (!$postulacion->solicitud_path) {
            return back()->with('error', 'El archivo no existe.');
        }

        return Storage::disk('public')->download($postulacion->solicitud_path);
    }
}