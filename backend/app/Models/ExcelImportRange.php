<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcelImportRange extends Model
{
    protected $fillable = [
        'plataforma',
        'nombre_tabla',
        'producto_slug',
        'hoja_excel',
        'fila_inicio',
        'fila_fin',
        'archivo_url',
        'bot_codigo_url',
        'bot_soporte_url',
        'columna_perfil',
        'columna_pin',
        'columna_numero',
        'columna_vendedor_igarlos',
        'columna_vendedor_nikol',
        'columna_costo',
        'columna_fecha_inicio',
        'columna_fecha_fin',
        'columna_estado',
        'columna_correo',
        'columna_password',
        'columna_cliente_acceso_usuario',
        'activo',
        'ultimo_sync_at',
        'ultimo_error',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fila_inicio' => 'integer',
        'fila_fin' => 'integer',
        'ultimo_sync_at' => 'datetime',
    ];

    public function columnMap(): array
    {
        return [
            'perfil' => $this->columna_perfil ?: 'F',
            'pin' => $this->columna_pin ?: 'G',
            'numero' => $this->columna_numero ?: 'H',
            'vendedor_igarlos' => $this->columna_vendedor_igarlos ?: 'I',
            'vendedor_nikol' => $this->columna_vendedor_nikol ?: 'J',
            'costo' => $this->columna_costo ?: 'K',
            'fecha_inicio' => $this->columna_fecha_inicio ?: 'L',
            'fecha_fin' => $this->columna_fecha_fin ?: 'M',
            'estado' => $this->columna_estado ?: 'N',
            'correo' => $this->columna_correo ?: 'U',
            'password' => $this->columna_password ?: 'V',
            'cliente_acceso_usuario' => $this->columna_cliente_acceso_usuario ?: 'X',
        ];
    }
}
