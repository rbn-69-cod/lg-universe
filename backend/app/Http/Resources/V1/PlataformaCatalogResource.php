<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlataformaCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'imagen' => $this->imagen,
            'precio' => (float) $this->precio,
            'descripcion' => $this->descripcion,
            'features' => $this->features ?: [],
            'activacion' => $this->activacion,
            'terminos' => $this->terminos,
            'activo' => (bool) $this->activo,
            'orden' => $this->orden,
        ];
    }
}
