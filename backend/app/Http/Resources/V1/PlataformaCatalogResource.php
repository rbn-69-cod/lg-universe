<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlataformaCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $duraciones = $this->activeDurations();

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'imagen' => $this->imagen,
            'precio' => (float) (($duraciones[0]['precio'] ?? null) ?? $this->precio),
            'descripcion' => $this->descripcion,
            'features' => $this->features ?: [],
            'activacion' => $this->activacion,
            'terminos' => $this->terminos,
            'activo' => (bool) $this->activo,
            'orden' => $this->orden,
            'duraciones' => $duraciones,
        ];
    }

    private function activeDurations(): array
    {
        return $this->whenLoaded('duraciones')
            ? $this->duraciones
                ->where('activo', true)
                ->sortBy('duracion_meses')
                ->values()
                ->map(fn ($duration) => [
                    'id' => $duration->id,
                    'duracion_meses' => (int) $duration->duracion_meses,
                    'precio' => (float) $duration->precio,
                    'activo' => (bool) $duration->activo,
                ])
                ->all()
            : [];
    }
}
