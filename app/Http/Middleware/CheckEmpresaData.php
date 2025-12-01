<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEmpresaData
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->tipo === 'empresa' && !Auth::user()->empresa) {
            // Si es empresa y no tiene datos, redirigir al formulario
            return redirect()->route('empresa.create');
        }

        return $next($request);
    }
}