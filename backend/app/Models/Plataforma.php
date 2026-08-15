<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
