<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plataforma extends Model
{
    protected $table = 'plataformas';

    protected $fillable = [
        'nombre',
        'imagen',
        'precio',
        'descripcion',
        'features',
        'activacion',
        'terminos',
        'activo',
        'orden',
    ];

    protected $casts = [
        'features' => 'array',
        'activo' => 'boolean',
    ];

    public function duraciones(): HasMany
    {
        return $this->hasMany(PlataformaDuracion::class)->orderBy('duracion_meses');
    }
}
