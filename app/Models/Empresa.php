<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'nombre_empresa', 'tipo_negocio', 'tamano_empresa', 'rfc', 'direccion', 
        'telefono_contacto', 'correo_contacto', 'representante_legal', 'puesto_representante',
        'pagina_web', 'descripcion_empresa', 'logo_path','constancia_fiscal_path','estado', 'motivo_rechazo', 'revisado_por', 'fecha_revision'
    ];

    // CONSTANTES PARA ESTADOS
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADA = 'aprobada';
    const ESTADO_RECHAZADA = 'rechazada';

    // RELACIÓN CON USUARIO
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // RELACIÓN CON REVISOR
    public function revisor()
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    // ✅ NUEVA RELACIÓN CON VACANTES
    public function vacantes()
    {
        return $this->hasMany(Vacante::class);
    }

    public function vacantesActivas()
    {
        return $this->vacantes()->where('estado', 'aprobada')->where('activa', true);
    }

    public function vacantesPendientes()
    {
        return $this->vacantes()->where('estado', 'pendiente');
    }

    // SCOPES ÚTILES
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', self::ESTADO_APROBADA);
    }

    // MÉTODOS DE ESTADO
    public function estaPendiente()
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaAprobada()
    {
        return $this->estado === self::ESTADO_APROBADA;
    }

    public function estaRechazada()
    {
        return $this->estado === self::ESTADO_RECHAZADA;
    }

    // ✅ NUEVO: Verificar si puede publicar vacantes
    public function puedePublicarVacantes()
    {
        return $this->estaAprobada();
    }
}