<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreInfoController extends Controller
{
    public function index(): Response
    {
        $settings = StoreSetting::current();

        return Inertia::render('Admin/StoreInfo/Index', [
            'settings' => [
                'store_name' => $settings->store_name,
                'store_email' => $settings->store_email,
                'store_phone' => $settings->store_phone,
                'store_whatsapp' => $settings->store_whatsapp,
                'store_address' => $settings->store_address,
                'facebook_url' => $settings->facebook_url,
                'instagram_url' => $settings->instagram_url,
                'tiktok_url' => $settings->tiktok_url,
                'youtube_url' => $settings->youtube_url,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'store_whatsapp' => ['nullable', 'string', 'max:30'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'tiktok_url' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = StoreSetting::current();
        StoreSetting::query()
            ->whereKey($settings->getKey())
            ->update($validated);

        return back()->with('success', 'Datos del negocio actualizados correctamente.');
    }
}