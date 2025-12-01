<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckServicioSocialAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado y sea el admin específico de Servicio Social
        if (Auth::check() && Auth::user()->email === 'servicio.social@itszn.edu.mx') {
            return $next($request);
        }

        // Si no es el admin correcto, redirigir con error
        return redirect()->route('admin.dashboard')
            ->with('error', '❌ No tienes permisos para acceder al Panel de Servicio Social. Solo el coordinador de Servicio Social puede acceder.');
    }
}