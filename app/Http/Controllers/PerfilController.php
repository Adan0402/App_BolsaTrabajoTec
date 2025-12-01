<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PerfilController extends Controller
{
    /**
     * Mostrar el formulario de perfil
     */
    public function index()
    {
        $usuario = Auth::user();
        return view('perfil.mi-perfil', compact('usuario'));
    }

    /**
 * Actualizar información personal
 */
public function actualizarInformacion(Request $request)
{
    $reglas = [
        'name' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|email|unique:usuarios,email,' . Auth::id(),
        'telefono' => 'nullable|string|max:20',
        'direccion' => 'nullable|string|max:500',
        'numero_control' => 'nullable|string|max:50',
        'carrera' => 'nullable|string|max:255',
        'semestre' => 'nullable|integer|min:1|max:20',
        'promedio' => 'nullable|numeric|min:0|max:10',
    ];

    $request->validate($reglas);

    $usuario = Auth::user();
    
    // Actualizar solo los campos que vienen en la request
    $camposActualizables = [
        'name', 'email', 'telefono', 'direccion', 
        'numero_control', 'carrera', 'semestre', 'promedio'
    ];
    
    $datosActualizar = [];
    foreach ($camposActualizables as $campo) {
        if ($request->has($campo)) {
            $datosActualizar[$campo] = $request->$campo;
        }
    }
    
    $usuario->update($datosActualizar);

    return response()->json([
        'success' => true,
        'message' => 'Información actualizada correctamente'
    ]);
}

    /**
     * Actualizar habilidades
     */
    public function actualizarHabilidades(Request $request)
    {
        $request->validate([
            'habilidades' => 'nullable|array',
            'habilidades.*' => 'string|max:100'
        ]);

        $usuario = Auth::user();
        $usuario->habilidades = $request->habilidades;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Habilidades actualizadas correctamente'
        ]);
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $usuario = Auth::user();

        if (!Hash::check($request->current_password, $usuario->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ], 422);
        }

        $usuario->password = Hash::make($request->new_password);
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña cambiada correctamente'
        ]);
    }

    /**
     * Subir foto de perfil
     */
    public function subirFoto(Request $request)
    {
        $request->validate([
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $usuario = Auth::user();

        // Eliminar foto anterior si existe
        if ($usuario->foto_perfil && Storage::exists($usuario->foto_perfil)) {
            Storage::delete($usuario->foto_perfil);
        }

        // Guardar nueva foto
        $path = $request->file('foto_perfil')->store('fotos-perfil', 'public');
        $usuario->foto_perfil = $path;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil actualizada',
            'foto_url' => Storage::url($path)
        ]);
    }

    /**
     * Subir CV
     */
    public function subirCV(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120'
        ]);

        $usuario = Auth::user();

        // Eliminar CV anterior si existe
        if ($usuario->cv_path && Storage::exists($usuario->cv_path)) {
            Storage::delete($usuario->cv_path);
        }

        // Guardar nuevo CV
        $path = $request->file('cv')->store('cvs', 'public');
        $usuario->cv_path = $path;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'CV actualizado correctamente',
            'cv_url' => Storage::url($path)
        ]);
    }

    /**
     * Obtener estadísticas del usuario
     */
    public function estadisticas()
    {
        $usuario = Auth::user();
        
        $estadisticas = [
            'total_postulaciones' => $usuario->postulaciones()->count(),
            'postulaciones_pendientes' => $usuario->postulaciones()->where('estado', 'pendiente')->count(),
            'postulaciones_aceptadas' => $usuario->postulaciones()->where('estado', 'aceptado')->count(),
            'postulaciones_rechazadas' => $usuario->postulaciones()->where('estado', 'rechazado')->count(),
        ];

        return response()->json($estadisticas);
    }
}