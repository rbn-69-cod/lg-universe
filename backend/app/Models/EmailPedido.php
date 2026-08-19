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
        'message_id',
        'thread_id',
        'imap_uid',
        'destinatario_original',
        'asunto',
        'remitente',
        'fecha_recibido',
        'raw_email',
        'html_body_original',
        'text_body_original',
        'cuerpo_html',
        'datos_extraidos',
        'extraction_status',
        'fecha_procesado_db',
    ];

    protected $casts = [
        'fecha_recibido' => 'datetime',
        'fecha_procesado_db' => 'datetime',
    ];
}
