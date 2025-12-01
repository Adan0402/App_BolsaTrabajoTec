<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Notificacion;
use App\Models\Vacante;
use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class EmpresaController extends Controller
{
    /**
     * Mostrar formulario para completar datos de empresa
     */
    public function create()
    {
        // Verificar que el usuario es una empresa y no tiene datos aún
        if (Auth::user()->tipo !== 'empresa') {
            return redirect('/dashboard')->with('error', 'Solo las empresas pueden acceder a esta página');
        }

        if (Auth::user()->empresa) {
            return redirect('/dashboard')->with('info', 'Ya completaste los datos de tu empresa');
        }

        return view('empresas.create');
    }

    /**
     * Guardar datos de la empresa
     */
    public function store(Request $request)
    {
        // Validar que el usuario es una empresa
        if (Auth::user()->tipo !== 'empresa') {
            return redirect('/dashboard')->with('error', 'Acceso no autorizado');
        }

        // Validar datos
        $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'tipo_negocio' => 'required|string|max:100',
            'tamano_empresa' => 'required|string|max:50',
            'rfc' => 'nullable|string|max:20|unique:empresas',
            'telefono_contacto' => 'required|string|max:20',
            'correo_contacto' => 'required|email|max:255',
            'representante_legal' => 'required|string|max:255',
            'puesto_representante' => 'required|string|max:255',
            'pagina_web' => 'nullable|url|max:255',
            'direccion' => 'nullable|string|max:500',
            'descripcion_empresa' => 'nullable|string|max:1000',
        ]);

        // Crear la empresa
        $empresa = Empresa::create([
            'user_id' => Auth::id(),
            'nombre_empresa' => $request->nombre_empresa,
            'tipo_negocio' => $request->tipo_negocio,
            'tamano_empresa' => $request->tamano_empresa,
            'rfc' => $request->rfc,
            'direccion' => $request->direccion,
            'telefono_contacto' => $request->telefono_contacto,
            'correo_contacto' => $request->correo_contacto,
            'representante_legal' => $request->representante_legal,
            'puesto_representante' => $request->puesto_representante,
            'pagina_web' => $request->pagina_web,
            'descripcion_empresa' => $request->descripcion_empresa,
            'estado' => 'pendiente',
        ]);

        // ✅ NOTIFICACIÓN DIRECTA Y SIMPLE
        $admins = Usuario::where('tipo', 'admin')->get();

        // Agregar log para ver qué está pasando
        \Log::info("🔔 EMPRESA CREADA: {$empresa->nombre_empresa}");
        \Log::info("🔔 ADMINS ENCONTRADOS: " . $admins->count());

        foreach ($admins as $admin) {
            try {
                $notificacion = Notificacion::create([
                    'user_id' => $admin->id,
                    'titulo' => '🏢 Nueva Empresa Registrada',
                    'mensaje' => "La empresa '{$empresa->nombre_empresa}' se ha registrado y está pendiente de validación.",
                    'tipo' => 'empresa_pendiente',
                    'data' => [
                        'empresa_id' => $empresa->id,
                        'action_url' => '/admin/empresas-pendientes'
                    ]
                ]);
                
                \Log::info("✅ NOTIFICACIÓN CREADA para admin: {$admin->name} (ID: {$notificacion->id})");
                
            } catch (\Exception $e) {
                \Log::error("❌ ERROR creando notificación: " . $e->getMessage());
            }
        }

        return redirect('/dashboard')->with('success', 'Datos de empresa guardados. Están pendientes de aprobación por el ITSZN.');
    }

    /**
     * Mostrar perfil de la empresa
     */
    public function perfil()
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return redirect('/dashboard')->with('error', 'No tienes una empresa registrada.');
        }

        return view('empresas.perfil', [
            'empresa' => $user->empresa,
            'usuario' => $user
        ]);
    }

    /**
     * Actualizar información general de la empresa
     */
    public function actualizarInformacionGeneral(Request $request)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'tipo_negocio' => 'required|string|max:100',
            'tamano_empresa' => 'required|in:micro,pequena,mediana,grande',
            'rfc' => 'nullable|string|max:20',
            'descripcion_empresa' => 'nullable|string|max:1000',
        ]);

        try {
            $user->empresa->update([
                'nombre_empresa' => $request->nombre_empresa,
                'tipo_negocio' => $request->tipo_negocio,
                'tamano_empresa' => $request->tamano_empresa,
                'rfc' => $request->rfc,
                'descripcion_empresa' => $request->descripcion_empresa,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Información general actualizada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar la información: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar información de contacto
     */
    public function actualizarContacto(Request $request)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'direccion' => 'nullable|string|max:500',
            'pagina_web' => 'nullable|url|max:255',
            'telefono_contacto' => 'required|string|max:20',
            'correo_contacto' => 'required|email|max:255',
        ]);

        try {
            $user->empresa->update([
                'direccion' => $request->direccion,
                'pagina_web' => $request->pagina_web,
                'telefono_contacto' => $request->telefono_contacto,
                'correo_contacto' => $request->correo_contacto,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Información de contacto actualizada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el contacto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar representante legal
     */
    public function actualizarRepresentante(Request $request)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'representante_legal' => 'required|string|max:255',
            'puesto_representante' => 'required|string|max:255',
        ]);

        try {
            $user->empresa->update([
                'representante_legal' => $request->representante_legal,
                'puesto_representante' => $request->puesto_representante,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Información del representante actualizada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el representante: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir/actualizar logo de la empresa
     */
    public function subirLogo(Request $request)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Eliminar logo anterior si existe
            if ($user->empresa->logo_path && Storage::exists($user->empresa->logo_path)) {
                Storage::delete($user->empresa->logo_path);
            }

            // Guardar nuevo logo
            $logoPath = $request->file('logo')->store('empresas/logos', 'public');

            $user->empresa->update([
                'logo_path' => $logoPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Logo actualizado correctamente.',
                'logo_url' => Storage::url($logoPath)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al subir el logo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir constancia fiscal
     */
    public function subirConstanciaFiscal(Request $request)
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'constancia_fiscal' => 'required|mimes:pdf|max:5120', // 5MB
        ]);

        try {
            // Eliminar constancia anterior si existe
            if ($user->empresa->constancia_fiscal_path && Storage::exists($user->empresa->constancia_fiscal_path)) {
                Storage::delete($user->empresa->constancia_fiscal_path);
            }

            // Guardar nueva constancia
            $constanciaPath = $request->file('constancia_fiscal')->store('empresas/constancias', 'public');

            $user->empresa->update([
                'constancia_fiscal_path' => $constanciaPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Constancia fiscal subida correctamente.',
                'documento_url' => Storage::url($constanciaPath)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al subir la constancia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de la empresa
     */
    public function estadisticas()
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $empresa = $user->empresa;
            
            $totalVacantes = $empresa->vacantes()->count();
            $vacantesActivas = $empresa->vacantes()
                ->where('estado', 'aprobada')
                ->where('activa', true)
                ->where('fecha_limite', '>=', now())
                ->count();
                
            $totalPostulaciones = Postulacion::whereIn('vacante_id', $empresa->vacantes()->pluck('id'))->count();
            $postulacionesPendientes = Postulacion::whereIn('vacante_id', $empresa->vacantes()->pluck('id'))
                ->where('estado', 'pendiente')
                ->count();

            return response()->json([
                'total_vacantes' => $totalVacantes,
                'vacantes_activas' => $vacantesActivas,
                'total_postulaciones' => $totalPostulaciones,
                'postulaciones_pendientes' => $postulacionesPendientes,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar contraseña (compartido con PerfilController)
     */
    public function cambiarPassword(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'La contraseña actual es incorrecta.'], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Contraseña actualizada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cambiar la contraseña: ' . $e->getMessage()
            ], 500);
        }
    }
}