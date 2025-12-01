<?php

namespace App\Http\Controllers;

use App\Models\Vacante;
use App\Models\Empresa;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VacanteController extends Controller
{
    /**
     * Mostrar formulario para crear vacante
     */
    public function create()
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea empresa y esté aprobada
        if ($user->tipo !== 'empresa') {
            return redirect('/dashboard')->with('error', 'Solo las empresas pueden publicar vacantes.');
        }
        
        if (!$user->empresa) {
            return redirect('/empresa/completar-datos')
                ->with('error', 'Primero debes completar los datos de tu empresa.');
        }
        
        if ($user->empresa->estado !== 'aprobada') {
            return redirect('/dashboard')->with('error', 'Tu empresa debe estar aprobada para publicar vacantes.');
        }

        return view('vacantes.create', compact('user'));
    }

    /**
     * Guardar nueva vacante
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Validaciones de seguridad
        if ($user->tipo !== 'empresa' || !$user->empresa || $user->empresa->estado !== 'aprobada') {
            return redirect('/dashboard')->with('error', 'No tienes permisos para publicar vacantes.');
        }

        // Validar datos del formulario
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|min:50',
            'requisitos' => 'required|string|min:30',
            'beneficios' => 'nullable|string',
            'tipo_contrato' => 'required|in:tiempo_completo,medio_tiempo,practicas,freelance,proyecto',
            'salario_min' => 'nullable|numeric|min:0',
            'salario_max' => 'nullable|numeric|min:0',
            'salario_mostrar' => 'boolean',
            'ubicacion' => 'required|string|max:255',
            'modalidad' => 'required|in:presencial,remoto,hibrido',
            'nivel_experiencia' => 'required|in:sin_experiencia,junior,mid,senior',
            'vacantes_disponibles' => 'required|integer|min:1',
            'fecha_limite' => 'required|date|after:today',
        ]);

        // Validación adicional de salarios
        if ($validated['salario_min'] && $validated['salario_max'] && 
            $validated['salario_min'] > $validated['salario_max']) {
            return back()->withErrors(['salario_min' => 'El salario mínimo no puede ser mayor al máximo.']);
        }

        try {
            // Crear la vacante
            $vacante = Vacante::create([
                'empresa_id' => $user->empresa->id,
                'titulo' => $validated['titulo'],
                'descripcion' => $validated['descripcion'],
                'requisitos' => $validated['requisitos'],
                'beneficios' => $validated['beneficios'],
                'tipo_contrato' => $validated['tipo_contrato'],
                'salario_min' => $validated['salario_min'],
                'salario_max' => $validated['salario_max'],
                'salario_mostrar' => $validated['salario_mostrar'] ?? true,
                'ubicacion' => $validated['ubicacion'],
                'modalidad' => $validated['modalidad'],
                'nivel_experiencia' => $validated['nivel_experiencia'],
                'vacantes_disponibles' => $validated['vacantes_disponibles'],
                'fecha_limite' => $validated['fecha_limite'],
                'estado' => 'pendiente', // Requiere aprobación del admin
                'activa' => true,
            ]);

            // ✅ NUEVO: NOTIFICAR A TODOS LOS ALUMNOS SOBRE NUEVA VACANTE
            $alumnos = Usuario::where('tipo', 'alumno')->get();
            foreach ($alumnos as $alumno) {
                Notificacion::crearVacanteNueva($alumno, $vacante);
            }

            // ✅ NUEVO: NOTIFICAR A LOS ADMINS SOBRE NUEVA VACANTE PENDIENTE
            $admins = Usuario::where('tipo', 'admin')->get();
            foreach ($admins as $admin) {
                Notificacion::nuevaVacanteCreada($vacante, $admin->id);
            }

            return redirect()->route('vacantes.index')
                ->with('success', '✅ Vacante publicada correctamente. Está pendiente de aprobación.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al publicar la vacante: ' . $e->getMessage());
        }
    }

    /**
     * Listar vacantes de la empresa
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para ver esta página.');
        }

        $vacantes = $user->empresa->vacantes()
            ->latest()
            ->get();

        return view('vacantes.index', compact('user', 'vacantes'));
    }

    /**
     * Mostrar formulario para editar vacante
     */
    public function edit(Vacante $vacante)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->tipo !== 'empresa' || !$user->empresa) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para editar vacantes.');
        }
        
        // Verificar que la vacante pertenece a la empresa del usuario
        if ($vacante->empresa_id !== $user->empresa->id) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para editar esta vacante.');
        }
        
        // Verificar que la vacante se puede editar (solo pendientes o aprobadas)
        if (!in_array($vacante->estado, ['pendiente', 'aprobada'])) {
            return redirect()->route('vacantes.index')
                ->with('error', 'No puedes editar una vacante rechazada o cerrada.');
        }

        return view('vacantes.edit', compact('user', 'vacante'));
    }

    /**
     * Actualizar vacante existente
     */
    public function update(Request $request, Vacante $vacante)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->tipo !== 'empresa' || !$user->empresa || $vacante->empresa_id !== $user->empresa->id) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para editar esta vacante.');
        }
        
        // Verificar que la vacante se puede editar
        if (!in_array($vacante->estado, ['pendiente', 'aprobada'])) {
            return redirect()->route('vacantes.index')
                ->with('error', 'No puedes editar una vacante rechazada o cerrada.');
        }

        // Validar datos del formulario
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|min:50',
            'requisitos' => 'required|string|min:30',
            'beneficios' => 'nullable|string',
            'tipo_contrato' => 'required|in:tiempo_completo,medio_tiempo,practicas,freelance,proyecto',
            'salario_min' => 'nullable|numeric|min:0',
            'salario_max' => 'nullable|numeric|min:0',
            'salario_mostrar' => 'boolean',
            'ubicacion' => 'required|string|max:255',
            'modalidad' => 'required|in:presencial,remoto,hibrido',
            'nivel_experiencia' => 'required|in:sin_experiencia,junior,mid,senior',
            'vacantes_disponibles' => 'required|integer|min:1',
            'fecha_limite' => 'required|date|after:today',
        ]);

        // Validación adicional de salarios
        if ($validated['salario_min'] && $validated['salario_max'] && 
            $validated['salario_min'] > $validated['salario_max']) {
            return back()->withErrors(['salario_min' => 'El salario mínimo no puede ser mayor al máximo.']);
        }

        try {
            // Actualizar la vacante
            $vacante->update([
                'titulo' => $validated['titulo'],
                'descripcion' => $validated['descripcion'],
                'requisitos' => $validated['requisitos'],
                'beneficios' => $validated['beneficios'],
                'tipo_contrato' => $validated['tipo_contrato'],
                'salario_min' => $validated['salario_min'],
                'salario_max' => $validated['salario_max'],
                'salario_mostrar' => $validated['salario_mostrar'] ?? true,
                'ubicacion' => $validated['ubicacion'],
                'modalidad' => $validated['modalidad'],
                'nivel_experiencia' => $validated['nivel_experiencia'],
                'vacantes_disponibles' => $validated['vacantes_disponibles'],
                'fecha_limite' => $validated['fecha_limite'],
                'estado' => 'pendiente', // Al editar, vuelve a estado pendiente para revisión
            ]);

            return redirect()->route('vacantes.index')
                ->with('success', '✅ Vacante actualizada correctamente. Está pendiente de nueva aprobación.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar la vacante: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar vacante
     */
    public function destroy(Vacante $vacante)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->tipo !== 'empresa' || !$user->empresa || $vacante->empresa_id !== $user->empresa->id) {
            return redirect('/dashboard')->with('error', 'No tienes permisos para eliminar esta vacante.');
        }

        try {
            $titulo = $vacante->titulo;
            $vacante->delete();

            return redirect()->route('vacantes.index')
                ->with('success', "✅ Vacante '{$titulo}' eliminada correctamente.");

        } catch (\Exception $e) {
            return redirect()->route('vacantes.index')
                ->with('error', 'Error al eliminar la vacante: ' . $e->getMessage());
        }
    }
}