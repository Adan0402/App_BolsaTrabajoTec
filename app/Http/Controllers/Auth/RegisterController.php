<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Notificacion;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Mostrar el formulario de registro
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Manejar una solicitud de registro
     */
    public function register(Request $request): RedirectResponse
    {
        // ✅ VALIDACIONES BASE PARA TODOS
        $validatedData = $request->validate([
            'tipo' => 'required|in:alumno,egresado,empresa',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|confirmed|min:8',
        ]);

        // ✅ VALIDACIONES CONDICIONALES PARA ALUMNOS
        if (in_array($request->tipo, ['alumno', 'egresado'])) {
            $request->validate([
                'numero_control' => 'required|string|size:8|unique:usuarios',
                'carrera' => 'required|string|max:255',
            ]);

            // Validar formato del número de control
            if (!preg_match('/^\d{8}$/', $request->numero_control)) {
                return back()->withErrors([
                    'numero_control' => 'El número de control debe tener exactamente 8 dígitos'
                ])->withInput();
            }
        }

        // ✅ VALIDACIONES CONDICIONALES PARA EMPRESAS
        if ($request->tipo === 'empresa') {
            $request->validate([
                'nombre_empresa' => 'required|string|max:255',
                'tipo_negocio' => 'required|string|max:100',
                'telefono_contacto' => 'required|string|max:20',
                'representante_legal' => 'required|string|max:255',
            ]);
        }

        // Crear usuario
        $userData = [
            'tipo' => $request->tipo,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        // ✅ AGREGAR DATOS ACADÉMICOS SOLO PARA ALUMNOS
        if (in_array($request->tipo, ['alumno', 'egresado'])) {
            $userData['numero_control'] = $request->numero_control;
            $userData['carrera'] = $request->carrera;
        }

        $user = Usuario::create($userData);

        // ✅ CREAR EMPRESA SI ES EL CASO
        if ($request->tipo === 'empresa') {
            // 🔥 CORRECCIÓN: Guardar la empresa en una variable
            $empresa = Empresa::create([
                'user_id' => $user->id,
                'nombre_empresa' => $request->nombre_empresa,
                'tipo_negocio' => $request->tipo_negocio,
                'tamano_empresa' => 'micro',
                'telefono_contacto' => $request->telefono_contacto,
                'correo_contacto' => $request->email,
                'representante_legal' => $request->representante_legal,
                'puesto_representante' => 'Representante',
                'estado' => 'pendiente',
            ]);

            // ✅ CORREGIDO: Ahora $empresa está definida
            Notificacion::nuevaEmpresaRegistrada($empresa);
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect('/dashboard')->with('success', 'Cuenta creada exitosamente.');
    }
}