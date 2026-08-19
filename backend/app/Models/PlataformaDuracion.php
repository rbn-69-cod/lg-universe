<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlataformaDuracion extends Model
{
    protected $table = 'plataforma_duraciones';

    protected $fillable = [
        'plataforma_id',
        'duracion_meses',
        'precio',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function plataforma(): BelongsTo
    {
        return $this->belongsTo(Plataforma::class);
    }
}
