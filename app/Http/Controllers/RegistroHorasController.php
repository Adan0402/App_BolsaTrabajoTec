<?php

namespace App\Http\Controllers;

use App\Models\ServicioSocial;
use App\Models\RegistroHorasSS;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistroHorasController extends Controller
{
    // 📅 MOSTRAR CALENDARIO/TABLA DE REGISTRO
    public function index($servicioSocialId)
    {
        $servicioSocial = ServicioSocial::with(['registrosHoras'])
            ->where('alumno_id', Auth::id())
            ->findOrFail($servicioSocialId);

        // Verificar que esté aprobado
        if (!in_array($servicioSocial->estado, ['empresa_acepto', 'en_proceso'])) {
            return redirect()->route('servicio-social.show', $servicioSocial->id)
                ->with('error', 'No puedes registrar horas hasta que tu Servicio Social sea aprobado completamente.');
        }

        $horasCompletadas = $servicioSocial->horas_totales;
        $horasRequeridas = $servicioSocial->horas_requeridas;
        $porcentaje = $servicioSocial->progreso_horas;

        // Obtener registros del mes actual
        $mesActual = date('m');
        $anoActual = date('Y');
        $registrosMes = $servicioSocial->registrosHoras()
            ->whereYear('fecha', $anoActual)
            ->whereMonth('fecha', $mesActual)
            ->get();

        return view('servicio_social.registro_horas', compact(
            'servicioSocial', 
            'horasCompletadas', 
            'horasRequeridas', 
            'porcentaje',
            'registrosMes',
            'mesActual',
            'anoActual'
        ));
    }

    // ➕ MOSTRAR FORMULARIO DE REGISTRO
    public function create($servicioSocialId)
    {
        $servicioSocial = ServicioSocial::where('alumno_id', Auth::id())
            ->findOrFail($servicioSocialId);

        return view('servicio_social.nuevo_registro_horas', compact('servicioSocial'));
    }

    // 💾 GUARDAR REGISTRO DE HORAS
public function store(Request $request, $servicioSocialId)
{
    $servicioSocial = ServicioSocial::where('alumno_id', Auth::id())
        ->findOrFail($servicioSocialId);

    $request->validate([
        'fecha' => 'required|date|before_or_equal:today',
        'horas_trabajadas' => 'required|integer|min:1|max:12',
        'actividades_realizadas' => 'required|string|max:1000',
        'evidencias' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
    ]);

    // Verificar que no exista registro para la misma fecha
    $registroExistente = RegistroHorasSS::where('servicio_social_id', $servicioSocial->id)
        ->where('fecha', $request->fecha)
        ->exists();

    if ($registroExistente) {
        return redirect()->back()
            ->with('error', 'Ya existe un registro de horas para esta fecha.');
    }

    // Procesar evidencia si existe
    $evidenciaPath = null;
    if ($request->hasFile('evidencias')) {
        $file = $request->file('evidencias');
        $extension = $file->getClientOriginalExtension();
        $fileName = 'evidencia_' . time() . '_' . Str::random(10) . '.' . $extension;
        $evidenciaPath = $file->storeAs('evidencias_servicio_social', $fileName, 'public');
    }

    // ✅ CORREGIDO: Crear registro con aprobado_empresa = NULL
    $registro = RegistroHorasSS::create([
        'servicio_social_id' => $servicioSocial->id,
        'fecha' => $request->fecha,
        'horas_trabajadas' => $request->horas_trabajadas,
        'actividades_realizadas' => $request->actividades_realizadas,
        'evidencias' => $evidenciaPath,
        'aprobado_empresa' => 0, // ✅ ESTABLECER EXPLÍCITAMENTE NULL
        'aprobado_jefe' => 0,    // ✅ ESTABLECER EXPLÍCITAMENTE NULL
    ]);

    // 🔔 NOTIFICAR A LA EMPRESA
    $empresaUsers = Usuario::whereHas('empresa', function($query) use ($servicioSocial) {
        $query->where('id', $servicioSocial->empresa_id);
    })->get();
    
    foreach ($empresaUsers as $user) {
        $this->crearNotificacion(
            $user->id,
            'Nuevo registro de horas - Servicio Social',
            "El alumno {$servicioSocial->alumno->name} registró {$request->horas_trabajadas} horas para el " . date('d/m/Y', strtotime($request->fecha)) . ". Actividades: " . Str::limit($request->actividades_realizadas, 100),
            'info',
            route('empresa.servicio-social.show', $servicioSocial->id)
        );
    }

    // 🔔 NOTIFICAR AL JEFE SS
    $jefeSS = Usuario::where('email', 'servicio.social@itszn.edu.mx')->first();
    if ($jefeSS) {
        $this->crearNotificacion(
            $jefeSS->id,
            'Nuevo registro de horas - Servicio Social',
            "El alumno {$servicioSocial->alumno->name} registró {$request->horas_trabajadas} horas para el " . date('d/m/Y', strtotime($request->fecha)),
            'info',
            route('admin.servicio-social.show', $servicioSocial->id)
        );
    }

    return redirect()->route('servicio-social.registro-horas', $servicioSocial->id)
        ->with('success', '✅ Horas registradas correctamente. Se notificó a la empresa y al coordinador.');
}

    // 👁️ VER DETALLES DE REGISTRO
    public function show($registroId)
    {
        $registro = RegistroHorasSS::with(['servicioSocial'])
            ->whereHas('servicioSocial', function($query) {
                $query->where('alumno_id', Auth::id());
            })
            ->findOrFail($registroId);

        return view('servicio_social.detalle_registro', compact('registro'));
    }

    // 📊 REPORTE MENSUAL DE HORAS
    public function reporteMensual($servicioSocialId, Request $request)
    {
        $servicioSocial = ServicioSocial::where('alumno_id', Auth::id())
            ->findOrFail($servicioSocialId);

        $mes = $request->get('mes', date('m'));
        $ano = $request->get('ano', date('Y'));

        $registros = $servicioSocial->registrosHoras()
            ->whereYear('fecha', $ano)
            ->whereMonth('fecha', $mes)
            ->orderBy('fecha', 'asc')
            ->get();

        $totalHorasMes = $registros->sum('horas_trabajadas');
        $diasTrabajados = $registros->count();

        // ✅ CORREGIDO: Calcular horas aprobadas correctamente
        $horasAprobadas = $registros->filter(function($registro) {
            return $registro->estaAprobado();
        })->sum('horas_trabajadas');

        // Estadísticas generales
        $estadisticas = [
            'total_horas' => $servicioSocial->horas_totales,
            'horas_mes' => $totalHorasMes,
            'dias_trabajados' => $diasTrabajados,
            'horas_aprobadas' => $horasAprobadas,
            'progreso_general' => $servicioSocial->progreso_horas,
        ];

        return view('servicio_social.reporte_mensual', compact(
            'servicioSocial', 
            'registros', 
            'estadisticas',
            'mes',
            'ano'
        ));
    }

    // 🗑️ ELIMINAR REGISTRO (solo si no está aprobado)
    public function destroy($registroId)
    {
        $registro = RegistroHorasSS::with(['servicioSocial'])
            ->whereHas('servicioSocial', function($query) {
                $query->where('alumno_id', Auth::id());
            })
            ->findOrFail($registroId);

        // ✅ CORREGIDO: Usar el método directamente
        if ($registro->estaAprobado()) {
            return redirect()->back()
                ->with('error', 'No puedes eliminar un registro que ya ha sido aprobado.');
        }

        // Eliminar archivo de evidencia si existe
        if ($registro->evidencias && Storage::disk('public')->exists($registro->evidencias)) {
            Storage::disk('public')->delete($registro->evidencias);
        }

        $servicioSocialId = $registro->servicio_social_id;
        $registro->delete();

        return redirect()->route('servicio-social.registro-horas', $servicioSocialId)
            ->with('success', '✅ Registro eliminado correctamente.');
    }

    // 🔧 MÉTODO PARA CREAR NOTIFICACIONES
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