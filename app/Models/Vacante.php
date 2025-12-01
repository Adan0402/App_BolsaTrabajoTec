<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacante extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descripcion',
        'requisitos',
        'beneficios',
        'tipo_contrato',
        'salario_min',
        'salario_max',
        'salario_mostrar',
        'ubicacion',
        'modalidad',
        'nivel_experiencia',
        'vacantes_disponibles',
        'fecha_limite',
        'estado',
        'motivo_rechazo',
        'activa'
    ];

    protected $casts = [
        'salario_min' => 'decimal:2',
        'salario_max' => 'decimal:2',
        'fecha_limite' => 'date',
        'salario_mostrar' => 'boolean',
        'activa' => 'boolean',
    ];

    // ✅ RELACIÓN CON EMPRESA
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ✅ RELACIÓN CON POSTULACIONES (aunque no exista aún)
   // public function postulaciones()
    //{
      //  return $this->hasMany(Postulacion::class);
    //}

    // ✅ SCOPES ÚTILES
    public function scopeActivas($query)
    {
        return $query->where('estado', 'aprobada')
                    ->where('activa', true)
                    ->where('fecha_limite', '>=', now());
    }

    public function scopePendientes($query)
    {
        return $this->where('estado', 'pendiente');
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobada');
    }

    // ✅ MÉTODOS DE ESTADO
    public function estaPendiente()
    {
        return $this->estado === 'pendiente';
    }

    public function estaAprobada()
    {
        return $this->estado === 'aprobada';
    }

    public function estaRechazada()
    {
        return $this->estado === 'rechazada';
    }

    public function estaActiva()
    {
        return $this->activa && $this->fecha_limite >= now();
    }

    // Relación con postulaciones
public function postulaciones()
{
    return $this->hasMany(Postulacion::class);
}

public function postulacionesCount()
{
    return $this->postulaciones()->count();
}

public function postulacionesPendientes()
{
    return $this->postulaciones()->where('estado', 'pendiente')->count();
}
}