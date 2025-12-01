<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JefeServicioSocial extends Model
{
    protected $table = 'jefes_servicio_social';
    
    protected $fillable = [
        'user_id', 'departamento'
    ];

    public function user()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function serviciosSociales()
    {
        return $this->hasMany(ServicioSocial::class, 'jefe_ss_id', 'user_id');
    }
}