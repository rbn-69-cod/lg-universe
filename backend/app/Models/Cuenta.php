<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    protected $fillable = [
        'producto_id',
        'email',
        'password',
        'perfiles_total',
        'perfiles_usados',
        'activo',
        'source_platforma',
        'source_hoja_excel',
        'source_row',
        'cliente_acceso_usuario',
        'bot_preferencia',
        'bot_hogar_url',
        'bot_temporal_url',
        'bot_acceso4_url',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'source_row' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function perfiles(): HasMany
    {
        return $this->hasMany(Perfil::class);
    }
}
