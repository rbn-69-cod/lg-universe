<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailPedido extends Model
{
    // Nombre real de la tabla
    protected $table = 'emails_pedidos';

    protected $primaryKey = 'id';

    // Tu tabla NO tiene created_at / updated_at
    public $timestamps = false;

    protected $fillable = [
        'destinatario_original',
        'asunto',
        'remitente',
        'fecha_recibido',
        'cuerpo_html',
        'datos_extraidos',
        'fecha_procesado_db',
    ];

    protected $casts = [
        'fecha_recibido' => 'datetime',
        'fecha_procesado_db' => 'datetime',
    ];
}
