<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroHorasSS extends Model
{
    protected $table = 'registro_horas_ss';
    
    protected $fillable = [
        'servicio_social_id', 'fecha', 'horas_trabajadas', 'actividades_realizadas',
        'evidencias', 'aprobado_empresa', 'aprobado_jefe', 'observaciones_empresa',
        'observaciones_jefe', 'fecha_aprobacion_empresa', 'fecha_aprobacion_jefe'
    ];

   protected $casts = [
    'fecha' => 'date',
    // 'aprobado_empresa' => 'boolean', // ❌ COMENTAR O ELIMINAR
    // 'aprobado_jefe' => 'boolean',    // ❌ COMENTAR O ELIMINAR
    'fecha_aprobacion_empresa' => 'datetime',
    'fecha_aprobacion_jefe' => 'datetime',
];

    public function servicioSocial()
    {
        return $this->belongsTo(ServicioSocial::class);
    }

    // ✅ CORREGIDO: Nueva lógica con valores numéricos
    public function estaAprobado()
    {
        return $this->aprobado_empresa === 1; // 1 = Aprobado
    }

    public function estaRechazado()
    {
        return $this->aprobado_empresa === 2; // 2 = Rechazado
    }

    public function estaPendiente()
    {
        return $this->aprobado_empresa === 0; // 0 = Pendiente
    }

    public function getEstadoAttribute()
    {
        if ($this->estaAprobado()) return 'aprobado';
        if ($this->estaRechazado()) return 'rechazado';
        return 'pendiente_empresa';
    }

    public function getNombreEvidenciaAttribute()
    {
        if (!$this->evidencias) return null;
        return basename($this->evidencias);
    }
}