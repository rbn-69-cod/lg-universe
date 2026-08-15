<x-layouts.app :title="__('Gestión de plataformas')">

<div class="p-6">

<h1 class="text-2xl font-bold mb-4">
Gestión de plataformas
</h1>

@if(session('success'))
<div class="mb-4 bg-green-100 text-green-800 px-4 py-2 rounded">
{{ session('success') }}
</div>
@endif

<div class="mb-4">
<a href="{{ route('admin.plataformas.create') }}"
class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
+ Nueva plataforma
</a>
</div>

<div class="overflow-x-auto bg-white rounded border">

<table class="min-w-full">

<thead class="bg-gray-100">
<tr>
<th class="px-4 py-2 text-left">ID</th>
<th class="px-4 py-2 text-left">Nombre</th>
<th class="px-4 py-2 text-left">Precio</th>
<th class="px-4 py-2 text-left">Estado</th>
<th class="px-4 py-2 text-left">Acciones</th>
</tr>
</thead>

<tbody>

@forelse($plataformas as $p)

<tr class="border-t">

<td class="px-4 py-2">
{{ $p->id }}
</td>

<td class="px-4 py-2">
{{ $p->nombre }}
</td>

<td class="px-4 py-2">
S/ {{ number_format($p->precio,2) }}
</td>

<td class="px-4 py-2">

@if($p->activo)

<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
Activa
</span>

@else

<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">
Inactiva
</span>

@endif

</td>

<td class="px-4 py-2 flex gap-2 items-center">

{{-- SUBIR --}}
<a href="{{ route('admin.plataformas.subir',$p->id) }}"
class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-sm font-bold">
⬆
</a>

{{-- BAJAR --}}
<a href="{{ route('admin.plataformas.bajar',$p->id) }}"
class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-sm font-bold">
⬇
</a>

{{-- EDITAR --}}
<a href="{{ route('admin.plataformas.edit',$p->id) }}"
class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs">
Editar
</a>

{{-- ELIMINAR --}}
<form action="{{ route('admin.plataformas.destroy',$p->id) }}"
method="POST">

@csrf
@method('DELETE')

<button
class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">
Eliminar
</button>

</form>

</td>

</tr>

@empty

<tr>
<td colspan="5" class="text-center py-4 text-gray-500">
No hay plataformas registradas
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

</div>

</x-layouts.app>