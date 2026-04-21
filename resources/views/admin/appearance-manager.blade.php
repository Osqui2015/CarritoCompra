@extends('layouts.admin')

@section('content')
    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-6">Gestión de Banners</h1>

        @if (session()->has('success'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="save" class="bg-white shadow rounded p-6 mb-8">
            <div class="mb-4">
                <label class="block font-semibold mb-1">Título</label>
                <input type="text" wire:model.defer="title" class="w-full border rounded px-3 py-2" required>
                @error('title')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Subtítulo</label>
                <input type="text" wire:model.defer="subtitle" class="w-full border rounded px-3 py-2">
                @error('subtitle')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Enlace</label>
                <input type="text" wire:model.defer="link" class="w-full border rounded px-3 py-2">
                @error('link')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Imagen</label>
                <input type="file" wire:model="newImage" class="w-full">
                @error('newImage')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="bg-zinc-600 text-white px-4 py-2 rounded hover:bg-zinc-700 transition">{{ $editingId ? 'Actualizar' : 'Crear' }}</button>
                <button type="button" wire:click="resetForm"
                    class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400 transition">Limpiar</button>
            </div>
        </form>

        <h2 class="text-xl font-semibold mb-4">Banners existentes</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded shadow">
                <thead>
                    <tr>
                        <th class="px-4 py-2">ID</th>
                        <th class="px-4 py-2">Título</th>
                        <th class="px-4 py-2">Subtítulo</th>
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
                            <td class="border px-4 py-2 flex gap-2">
                                <button wire:click="edit({{ $banner->id }})"
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transition">Editar</button>
                                <button wire:click="delete({{ $banner->id }})"
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transition"
                                    onclick="return confirm('¿Eliminar este banner?')">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500 py-4">No hay banners registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection


