<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PlataformaCatalogResource;
use App\Models\Plataforma;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlataformaCatalogController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        $plataformas = Plataforma::query()
            ->with(['duraciones' => fn ($query) => $query
                ->where('activo', true)
                ->orderBy('duracion_meses')])
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereDoesntHave('duraciones')
                    ->orWhereHas('duraciones', fn ($durationQuery) => $durationQuery->where('activo', true));
            })
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return PlataformaCatalogResource::collection($plataformas);
    }
}
