<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perfil extends Model
{
    protected $table = 'perfiles';

    protected $fillable = [
        'cuenta_id',
        'nombre_perfil',
        'pin',
        'numero',
        'vendedor',
        'costo',
        'fecha_inicio',
        'fecha_fin',
        'estado_excel',
        'ocupado',
        'source_platforma',
        'source_hoja_excel',
        'source_row',
        'cliente_acceso_usuario',
    ];

    protected $casts = [
        'ocupado' => 'boolean',
        'costo' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'source_row' => 'integer',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }
}
