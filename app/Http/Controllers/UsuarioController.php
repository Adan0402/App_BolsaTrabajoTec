<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Mostrar formulario para editar usuario
     */
    public function edit($id)
    {
        $usuario = Usuario::findOrFail($id);
        return view('usuarios.edit', compact('usuario'));
    }

    /**
 * Actualizar usuario
 */
public function update(Request $request, $id)
{
    $usuario = Usuario::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:usuarios,email,' . $usuario->id,
        'telefono' => 'nullable|string|max:20',
        'direccion' => 'nullable|string|max:500',
        'tipo' => 'required|in:alumno,egresado,empresa,admin',
        'activo' => 'required|boolean',
        'numero_control' => 'nullable|string|max:50',
        'carrera' => 'nullable|string|max:255',
        'semestre' => 'nullable|integer|min:1|max:20',
        'promedio' => 'nullable|numeric|min:0|max:10',
    ]);

    // Preparar datos manualmente para asegurar tipos correctos
    $datos = [
        'name' => $request->name,
        'email' => $request->email,
        'telefono' => $request->telefono,
        'direccion' => $request->direccion,
        'tipo' => $request->tipo,
        'activo' => (bool)$request->activo, // ✅ FORZAR A BOOLEAN
    ];

    // Manejar campos académicos según el tipo
    if (in_array($request->tipo, ['alumno', 'egresado'])) {
        $datos['numero_control'] = $request->numero_control;
        $datos['carrera'] = $request->carrera;
        $datos['semestre'] = $request->semestre ? (int)$request->semestre : null;
        $datos['promedio'] = $request->promedio ? (float)$request->promedio : null;
    } else {
        // Limpiar campos académicos para empresas y admin
        $datos['numero_control'] = null;
        $datos['carrera'] = null;
        $datos['semestre'] = null;
        $datos['promedio'] = null;
    }

    $usuario->update($datos);

    return response()->json([
        'success' => true,
        'message' => 'Usuario actualizado correctamente'
    ]);
}

    /**
     * Cambiar estado activo/inactivo
     */
    public function cambiarEstado($id)
    {
        $usuario = Usuario::findOrFail($id);

        // No permitir desactivarse a sí mismo
        if ($usuario->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propio usuario'
            ], 422);
        }

        $usuario->update([
            'activo' => !$usuario->activo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del usuario actualizado correctamente',
            'nuevo_estado' => $usuario->activo ? 'activo' : 'inactivo'
        ]);
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function cambiarPassword(Request $request, $id)
    {
        $request->validate([
            'nueva_password' => 'required|string|min:8|confirmed',
        ]);

        $usuario = Usuario::findOrFail($id);
        $usuario->update([
            'password' => Hash::make($request->nueva_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }
}