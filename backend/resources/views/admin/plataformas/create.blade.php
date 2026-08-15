<x-layouts.app :title="__('Crear plataforma')">

<div class="p-6 max-w-xl">

<h1 class="text-2xl font-bold mb-6">
Nueva plataforma
</h1>

<form method="POST"
action="{{ route('admin.plataformas.store') }}"
class="space-y-4">

@csrf

<div>

<label class="block text-sm">
Nombre
</label>

<input type="text"
name="nombre"
value="{{ old('nombre') }}"
class="w-full border rounded px-3 py-2"
required>

</div>


<div>

<label class="block text-sm">
URL Imagen
</label>

<input type="text"
name="imagen"
value="{{ old('imagen') }}"
class="w-full border rounded px-3 py-2"
required>

</div>


<div>

<label class="block text-sm">
Precio
</label>

<input type="number"
step="0.01"
name="precio"
value="{{ old('precio') }}"
class="w-full border rounded px-3 py-2"
required>

</div>


<div>

<label class="block text-sm">
Características
</label>

<textarea
name="features"
rows="4"
class="w-full border rounded px-3 py-2">{{ old('features') }}</textarea>

</div>


<div class="flex gap-2 pt-2">

<button
class="bg-blue-600 text-white px-4 py-2 rounded">
Guardar
</button>

<a href="{{ route('admin.plataformas.index') }}"
class="bg-gray-300 px-4 py-2 rounded">
Cancelar
</a>

</div>

</form>

</div>

</x-layouts.app>