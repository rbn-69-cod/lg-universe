<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExcelImportRange;
use App\Services\Excel\NetflixPremiumExcelImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExcelImportRangeController extends Controller
{
    public function index()
    {
        $ranges = ExcelImportRange::query()
            ->where('plataforma', 'Netflix Premium')
            ->orderBy('hoja_excel')
            ->orderBy('fila_inicio')
            ->get();

        $defaultUrl = old('archivo_url')
            ?: optional($ranges->firstWhere('archivo_url', '!=', null))->archivo_url
            ?: 'https://docs.google.com/spreadsheets/d/1XOmb1vaY4ZRGDiZuINggMDaq4AthM13X/edit?usp=sharing&ouid=111245760545075131727&rtpof=true&sd=true';

        return view('admin.excel-import-ranges.index', compact('ranges', 'defaultUrl'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['plataforma'] = 'Netflix Premium';
        $data['activo'] = $request->boolean('activo');
        $data = $this->normalizeColumns($data);

        $this->assertNoOverlap($data);

        ExcelImportRange::create($data);

        return back()->with('success', 'Rango agregado correctamente.');
    }

    public function update(Request $request, ExcelImportRange $excelImportRange)
    {
        $data = $this->validated($request);
        $data['plataforma'] = 'Netflix Premium';
        $data['activo'] = $request->boolean('activo');
        $data = $this->normalizeColumns($data);

        $this->assertNoOverlap($data, $excelImportRange->id);

        $excelImportRange->update($data);

        return back()->with('success', 'Rango actualizado correctamente.');
    }

    public function toggle(ExcelImportRange $excelImportRange)
    {
        if (! $excelImportRange->activo) {
            $data = $excelImportRange->only([
                'plataforma',
                'hoja_excel',
                'fila_inicio',
                'fila_fin',
                'archivo_url',
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
            ]);
            $data['activo'] = true;

            $this->assertNoOverlap($data, $excelImportRange->id);
        }

        $excelImportRange->update(['activo' => ! $excelImportRange->activo]);

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy(ExcelImportRange $excelImportRange)
    {
        $excelImportRange->delete();

        return back()->with('success', 'Rango eliminado correctamente.');
    }

    public function sync(NetflixPremiumExcelImporter $importer)
    {
        try {
            $stats = $importer->sync();

            return back()->with('success', 'Sincronizacion completada: '.json_encode($stats, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            ExcelImportRange::query()
                ->where('plataforma', 'Netflix Premium')
                ->where('activo', true)
                ->update(['ultimo_error' => $e->getMessage()]);

            return back()->withErrors(['sync' => $e->getMessage()]);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'archivo_url' => ['required', 'url'],
            'hoja_excel' => ['required', 'string', 'max:255'],
            'fila_inicio' => ['required', 'integer', 'min:1'],
            'fila_fin' => ['required', 'integer', 'gte:fila_inicio'],
            'columna_perfil' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_pin' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_numero' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_vendedor_igarlos' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_vendedor_nikol' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_costo' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_fecha_inicio' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_fecha_fin' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_estado' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_correo' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_password' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_cliente_acceso_usuario' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
        ], [
            'fila_fin.gte' => 'La fila final no puede ser menor que la fila inicial.',
            '*.regex' => 'Las columnas deben ser letras validas de Excel, por ejemplo F, U o AA.',
        ]);
    }

    private function normalizeColumns(array $data): array
    {
        foreach ($this->columnFields() as $field) {
            $data[$field] = strtoupper(trim((string) $data[$field]));
        }

        return $data;
    }

    private function columnFields(): array
    {
        return [
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
        ];
    }

    private function assertNoOverlap(array $data, ?int $ignoreId = null): void
    {
        if (! ($data['activo'] ?? false)) {
            return;
        }

        $overlap = ExcelImportRange::query()
            ->where('hoja_excel', $data['hoja_excel'])
            ->where('archivo_url', $data['archivo_url'])
            ->where('activo', true)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('fila_inicio', '<=', $data['fila_fin'])
            ->where('fila_fin', '>=', $data['fila_inicio'])
            ->first();

        if ($overlap) {
            throw ValidationException::withMessages([
                'fila_inicio' => "El rango {$data['fila_inicio']}-{$data['fila_fin']} se superpone con {$overlap->plataforma} {$overlap->fila_inicio}-{$overlap->fila_fin} en la hoja {$data['hoja_excel']}.",
            ]);
        }
    }
}
