<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioSocial extends Model
{
    protected $table = 'servicio_social';
    
    protected $fillable = [
        'postulacion_id', 'alumno_id', 'empresa_id', 'vacante_id', 'jefe_ss_id',
        'carrera', 'semestre', 'numero_control', 
        'fecha_inicio', 'fecha_fin_estimada', 'fecha_fin_real',
        'horas_requeridas', 'horas_completadas', 'horas_pendientes',
        'estado', 'empresa_acepta', 'jefe_ss_aprueba',
        'observaciones_empresa', 'observaciones_jefe',
        'supervisor_empresa', 'email_supervisor', 'telefono_supervisor',
        'nombre_proyecto', 'actividades_principales',
        'fecha_solicitud', 'fecha_empresa_acepto', 'fecha_jefe_aprobo',
        'fecha_inicio_proceso', 'fecha_finalizacion'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin_estimada' => 'date',
        'fecha_fin_real' => 'date',
        'empresa_acepta' => 'boolean',
        'jefe_ss_aprueba' => 'boolean',
        'fecha_solicitud' => 'datetime',
        'fecha_empresa_acepto' => 'datetime',
        'fecha_jefe_aprobo' => 'datetime',
        'fecha_inicio_proceso' => 'datetime',
        'fecha_finalizacion' => 'datetime',
    ];

    // RELACIONES
    public function postulacion()
    {
        return $this->belongsTo(Postulacion::class);
    }

    public function alumno()
    {
        return $this->belongsTo(Usuario::class, 'alumno_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function vacante()
    {
        return $this->belongsTo(Vacante::class);
    }

    public function jefeServicioSocial()
    {
        return $this->belongsTo(Usuario::class, 'jefe_ss_id');
    }

    public function registrosHoras()
    {
        return $this->hasMany(RegistroHorasSS::class)->orderBy('fecha', 'desc');
    }

    // MÉTODOS DE AYUDA
    public function puedeSolicitar()
    {
        return $this->postulacion->estado === 'aceptado' && !$this->exists;
    }

    public function porcentajeCompletado()
    {
        if ($this->horas_requeridas == 0) return 0;
        return round(($this->horas_completadas / $this->horas_requeridas) * 100, 2);
    }

    public function estaAprobado()
    {
        return $this->empresa_acepta && $this->jefe_ss_aprueba;
    }

    public function diasRestantes()
    {
        if (!$this->fecha_fin_estimada) return null;
        return now()->diffInDays($this->fecha_fin_estimada, false);
    }

    // Método para calcular horas totales
    public function getHorasTotalesAttribute()
    {
        return $this->registrosHoras->sum('horas_trabajadas');
    }

    // Método para calcular progreso
    public function getProgresoHorasAttribute()
    {
        if ($this->horas_requeridas == 0) return 0;
        return min(100, round(($this->horas_totales / $this->horas_requeridas) * 100, 2));
    }

    // Método para obtener horas por mes
    public function horasPorMes($year = null, $month = null)
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');
        
        return $this->registrosHoras()
            ->whereYear('fecha', $year)
            ->whereMonth('fecha', $month)
            ->sum('horas_trabajadas');
    }
}