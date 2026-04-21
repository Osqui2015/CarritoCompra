<div class="container mx-auto py-8">
	<h1 class="mb-6 text-2xl font-bold">Gestion de Banners</h1>

	@if (session()->has('success'))
		<div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">
			{{ session('success') }}
		</div>
	@endif

	<form wire:submit.prevent="save" class="mb-8 rounded bg-white p-6 shadow">
		<div class="mb-4">
			<label class="mb-1 block font-semibold">Titulo</label>
			<input type="text" wire:model.defer="title" class="w-full rounded border px-3 py-2" required>
			@error('title')
				<span class="text-sm text-red-600">{{ $message }}</span>
			@enderror
		</div>
		<div class="mb-4">
			<label class="mb-1 block font-semibold">Subtitulo</label>
			<input type="text" wire:model.defer="subtitle" class="w-full rounded border px-3 py-2">
			@error('subtitle')
				<span class="text-sm text-red-600">{{ $message }}</span>
			@enderror
		</div>
		<div class="mb-4">
			<label class="mb-1 block font-semibold">Enlace</label>
			<input type="text" wire:model.defer="link" class="w-full rounded border px-3 py-2">
			@error('link')
				<span class="text-sm text-red-600">{{ $message }}</span>
			@enderror
		</div>
		<div class="mb-4">
			<label class="mb-1 block font-semibold">Imagen</label>
			<input type="file" wire:model="newImage" class="w-full">
			@error('newImage')
				<span class="text-sm text-red-600">{{ $message }}</span>
			@enderror
		</div>
		<div class="flex gap-2">
			<button type="submit"
				class="rounded bg-zinc-600 px-4 py-2 text-white transition hover:bg-zinc-700">{{ $editingId ? 'Actualizar' : 'Crear' }}</button>
			<button type="button" wire:click="resetForm"
				class="rounded bg-gray-300 px-4 py-2 transition hover:bg-gray-400">Limpiar</button>
		</div>
	</form>

	<h2 class="mb-4 text-xl font-semibold">Banners existentes</h2>
	<div class="overflow-x-auto">
		<table class="min-w-full rounded bg-white shadow">
			<thead>
				<tr>
					<th class="px-4 py-2">ID</th>
					<th class="px-4 py-2">Titulo</th>
					<th class="px-4 py-2">Subtitulo</th>
					<th class="px-4 py-2">Enlace</th>
					<th class="px-4 py-2">Imagen</th>
					<th class="px-4 py-2">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse ($banners as $banner)
					<tr>
						<td class="border px-4 py-2">{{ $banner->id }}</td>
						<td class="border px-4 py-2">{{ $banner->title }}</td>
						<td class="border px-4 py-2">{{ $banner->subtitle }}</td>
						<td class="border px-4 py-2">{{ $banner->link }}</td>
						<td class="border px-4 py-2">
							@if ($banner->image_url)
								<img src="{{ $banner->image_url }}" alt="Banner" class="h-12 rounded">
							@else
								<span class="text-gray-400">Sin imagen</span>
							@endif
						</td>
						<td class="flex gap-2 border px-4 py-2">
							<button wire:click="edit({{ $banner->id }})"
								class="rounded bg-yellow-400 px-2 py-1 text-white transition hover:bg-yellow-500">Editar</button>
							<button wire:click="delete({{ $banner->id }})"
								class="rounded bg-red-500 px-2 py-1 text-white transition hover:bg-red-600"
								onclick="return confirm('¿Eliminar este banner?')">Eliminar</button>
						</td>
					</tr>
				@empty
					<tr>
						<td colspan="6" class="py-4 text-center text-gray-500">No hay banners registrados.</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	<div class="mt-4">
		{{ $banners->links() }}
	</div>
</div>
