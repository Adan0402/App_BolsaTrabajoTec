<?php

namespace App\Http\Controllers;

use App\Models\ServicioSocial;
use App\Models\RegistroHorasSS;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServicioSocialEmpresaController extends Controller
{
    // LISTAR SOLICITUDES DE SERVICIO SOCIAL
    public function index()
    {
        $empresa = Auth::user()->empresa;
        
        $solicitudes = ServicioSocial::with(['alumno', 'vacante', 'postulacion'])
            ->where('empresa_id', $empresa->id)
            ->whereIn('estado', ['jefe_aprobo', 'empresa_acepto', 'en_proceso', 'completado'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('empresas.servicio_social.index', compact('solicitudes', 'empresa'));
    }

    // MOSTRAR DETALLES DE SOLICITUD
    public function show(ServicioSocial $servicioSocial)
    {
        // Verificar que la solicitud pertenezca a la empresa del usuario autenticado
        if ($servicioSocial->empresa_id !== Auth::user()->empresa->id) {
            abort(403, 'No tienes permiso para ver esta solicitud.');
        }

        $servicioSocial->load(['alumno', 'vacante', 'postulacion']);

        return view('empresas.servicio_social.show', compact('servicioSocial'));
    }

    // ✅ CORREGIDO: APROBAR SOLICITUD DE SERVICIO SOCIAL
    public function aprobar(Request $request, ServicioSocial $servicioSocial)
    {
        // Verificar que la solicitud pertenezca a la empresa del usuario autenticado
        if ($servicioSocial->empresa_id !== Auth::user()->empresa->id) {
            abort(403, 'No tienes permiso para aprobar esta solicitud.');
        }

        // ✅ CORREGIDO: Validar que esté en estado jefe_aprobo (no solicitado)
        if ($servicioSocial->estado !== 'jefe_aprobo') {
            return redirect()->back()
                ->with('error', 'Esta solicitud ya ha sido procesada o no está lista para aprobación.');
        }

        $request->validate([
            'observaciones_empresa' => 'nullable|string|max:1000',
        ]);

        // Actualizar estado
        $servicioSocial->update([
            'estado' => 'empresa_acepto',
            'empresa_acepta' => true,
            'observaciones_empresa' => $request->observaciones_empresa,
            'fecha_empresa_acepto' => now(),
            'fecha_inicio_proceso' => now(), // Inicia el proceso oficialmente
        ]);

        // ✅ CREAR NOTIFICACIÓN PARA EL ALUMNO
        $this->crearNotificacion(
            $servicioSocial->alumno_id,
            '¡Empresa aceptó tu Servicio Social! 🎉',
            "La empresa {$servicioSocial->empresa->nombre_empresa} ha aceptado tu solicitud de Servicio Social para el puesto: {$servicioSocial->vacante->titulo}. ¡Ya puedes comenzar a registrar tus horas!",
            'success',
            route('servicio-social.show', $servicioSocial->id)
        );

        // ✅ CREAR NOTIFICACIÓN PARA EL JEFE DE SERVICIO SOCIAL
        $jefeSS = Usuario::where('email', 'servicio.social@itszn.edu.mx')->first();
        if ($jefeSS) {
            $this->crearNotificacion(
                $jefeSS->id,
                'Empresa aceptó Servicio Social',
                "La empresa {$servicioSocial->empresa->nombre_empresa} aceptó la solicitud de Servicio Social del alumno {$servicioSocial->alumno->name} para el puesto: {$servicioSocial->vacante->titulo}.",
                'info',
                route('admin.servicio-social.show', $servicioSocial->id)
            );
        }

        return redirect()->route('empresa.servicio-social.index')
            ->with('success', '✅ Solicitud de Servicio Social aceptada correctamente. El alumno puede comenzar a registrar horas.');
    }

    // ✅ CORREGIDO: RECHAZAR SOLICITUD DE SERVICIO SOCIAL
    public function rechazar(Request $request, ServicioSocial $servicioSocial)
    {
        // Verificar que la solicitud pertenezca a la empresa del usuario autenticado
        if ($servicioSocial->empresa_id !== Auth::user()->empresa->id) {
            abort(403, 'No tienes permiso para rechazar esta solicitud.');
        }

        // ✅ CORREGIDO: Validar que esté en estado jefe_aprobo (no solicitado)
        if ($servicioSocial->estado !== 'jefe_aprobo') {
            return redirect()->back()
                ->with('error', 'Esta solicitud ya ha sido procesada o no está disponible para rechazo.');
        }

        $request->validate([
            'observaciones_empresa' => 'required|string|max:1000',
        ]);

        // Actualizar estado
        $servicioSocial->update([
            'estado' => 'rechazado',
            'empresa_acepta' => false,
            'observaciones_empresa' => $request->observaciones_empresa,
        ]);

        // ✅ CREAR NOTIFICACIÓN PARA EL ALUMNO
        $this->crearNotificacion(
            $servicioSocial->alumno_id,
            'Respuesta de Servicio Social - Empresa',
            "La empresa {$servicioSocial->empresa->nombre_empresa} ha rechazado tu solicitud de Servicio Social. Motivo: {$request->observaciones_empresa}",
            'error',
            route('servicio-social.show', $servicioSocial->id)
        );

        // ✅ CREAR NOTIFICACIÓN PARA EL JEFE DE SERVICIO SOCIAL
        $jefeSS = Usuario::where('email', 'servicio.social@itszn.edu.mx')->first();
        if ($jefeSS) {
            $this->crearNotificacion(
                $jefeSS->id,
                'Empresa rechazó Servicio Social',
                "La empresa {$servicioSocial->empresa->nombre_empresa} rechazó la solicitud de Servicio Social del alumno {$servicioSocial->alumno->name}.",
                'warning',
                route('admin.servicio-social.show', $servicioSocial->id)
            );
        }

        return redirect()->route('empresa.servicio-social.index')
            ->with('success', '✅ Solicitud de Servicio Social rechazada correctamente.');
    }

    // 📊 MOSTRAR REGISTROS DE HORAS DE UN SERVICIO SOCIAL
public function registrosHoras($servicioSocialId)
{
    $servicioSocial = ServicioSocial::with(['registrosHoras', 'alumno', 'empresa', 'vacante'])
        ->where('empresa_id', Auth::user()->empresa->id)
        ->findOrFail($servicioSocialId);

    // ✅ CALCULAR LAS ESTADÍSTICAS QUE FALTAN
    $horasAprobadas = $servicioSocial->registrosHoras
        ->where('aprobado_empresa', true)
        ->sum('horas_trabajadas');

    $horasPendientes = $servicioSocial->registrosHoras
        ->where('aprobado_empresa', null)
        ->sum('horas_trabajadas');

    $horasRechazadas = $servicioSocial->registrosHoras
        ->where('aprobado_empresa', false)
        ->sum('horas_trabajadas');

    $horasTotales = $servicioSocial->horas_totales;

    $progreso = $servicioSocial->horas_requeridas > 0 
        ? round(($horasTotales / $servicioSocial->horas_requeridas) * 100, 1)
        : 0;

    // ✅ PASAR TODAS LAS VARIABLES NECESARIAS
    return view('empresas.servicio_social.registros_horas', compact(
        'servicioSocial',
        'horasAprobadas',
        'horasPendientes',
        'horasRechazadas',
        'progreso'
    ));
}

    // ✅ APROBAR REGISTRO DE HORAS
    public function aprobarRegistro(Request $request, $registroId)
    {
        $registro = RegistroHorasSS::with(['servicioSocial'])
            ->whereHas('servicioSocial', function($query) {
                $query->where('empresa_id', Auth::user()->empresa->id);
            })
            ->findOrFail($registroId);

        $registro->update([
            'aprobado_empresa' => true,
            'observaciones_empresa' => $request->observaciones,
            'fecha_aprobacion_empresa' => now(),
        ]);

        // Notificar al alumno
        $this->crearNotificacion(
            $registro->servicioSocial->alumno_id,
            'Registro de horas aprobado',
            "La empresa ha aprobado tu registro de {$registro->horas_trabajadas} horas del " . $registro->fecha->format('d/m/Y'),
            'success',
            route('servicio-social.registro.show', $registro->id)
        );

        return redirect()->back()->with('success', 'Registro de horas aprobado correctamente.');
    }

    // ❌ RECHAZAR REGISTRO DE HORAS
    public function rechazarRegistro(Request $request, $registroId)
    {
        $request->validate([
            'observaciones' => 'required|string|max:500'
        ]);

        $registro = RegistroHorasSS::with(['servicioSocial'])
            ->whereHas('servicioSocial', function($query) {
                $query->where('empresa_id', Auth::user()->empresa->id);
            })
            ->findOrFail($registroId);

        $registro->update([
            'aprobado_empresa' => false,
            'observaciones_empresa' => $request->observaciones,
        ]);

        // Notificar al alumno
        $this->crearNotificacion(
            $registro->servicioSocial->alumno_id,
            'Registro de horas requiere correcciones',
            "La empresa ha solicitado correcciones en tu registro del " . $registro->fecha->format('d/m/Y') . ". Motivo: " . $request->observaciones,
            'warning',
            route('servicio-social.registro.show', $registro->id)
        );

        return redirect()->back()->with('success', 'Registro de horas rechazado. Se notificó al alumno.');
    }

    // ✅ MÉTODO PARA CREAR NOTIFICACIONES
    private function crearNotificacion($userId, $titulo, $mensaje, $tipo = 'info', $url = null)
    {
        Notificacion::create([
            'user_id' => $userId,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'leida' => false,
            'url' => $url,
            'created_at' => now(),
        ]);
    }
}