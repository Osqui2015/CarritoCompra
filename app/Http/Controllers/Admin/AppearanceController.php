<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function index(): Response
    {
        $settings = StoreSetting::current();
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Appearance/Index', [
            'settings' => [
                'low_stock_threshold' => $settings->low_stock_threshold,
                'sales_whatsapp' => $settings->sales_whatsapp,
                'store_address' => $settings->store_address,
                'business_hours' => $settings->business_hours,
                'hero_banner_title' => $settings->hero_banner_title,
                'hero_banner_subtitle' => $settings->hero_banner_subtitle,
                'hero_banner_link_type' => $settings->hero_banner_link_type,
                'hero_banner_link_value' => $settings->hero_banner_link_value,
                'hero_banner_url' => $settings->hero_banner_url,
            ],
            'categories' => $categories->map(fn(Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values()->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $linkType = (string) $request->input('hero_banner_link_type', 'url');

        $validated = $request->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000'],
            'sales_whatsapp' => ['nullable', 'string', 'max:40'],
            'store_address' => ['nullable', 'string', 'max:160'],
            'business_hours' => ['nullable', 'string', 'max:200'],
            'hero_banner_title' => ['required', 'string', 'max:120'],
            'hero_banner_subtitle' => ['nullable', 'string', 'max:220'],
            'hero_banner_link_type' => ['required', 'in:url,category'],
            'hero_banner_link_value' => $linkType === 'category'
                ? ['required', 'string', Rule::exists('categories', 'slug')]
                : ['required', 'string', 'max:255'],
            'hero_banner_image' => ['nullable', 'image', 'max:4096'],
            'remove_hero_banner_image' => ['nullable', 'boolean'],
        ]);

        $settings = StoreSetting::current();
        $settings->fill([
            'low_stock_threshold' => (int) $validated['low_stock_threshold'],
            'sales_whatsapp' => $validated['sales_whatsapp'] ?? null,
            'store_address' => $validated['store_address'] ?? null,
            'business_hours' => $validated['business_hours'] ?? null,
            'hero_banner_title' => $validated['hero_banner_title'],
            'hero_banner_subtitle' => $validated['hero_banner_subtitle'] ?? null,
            'hero_banner_link_type' => $validated['hero_banner_link_type'],
            'hero_banner_link_value' => $validated['hero_banner_link_value'],
        ])->save();

        if ((bool) ($validated['remove_hero_banner_image'] ?? false)) {
            $settings->clearMediaCollection('hero_banner');
        }

        if ($request->hasFile('hero_banner_image')) {
            $settings->clearMediaCollection('hero_banner');
            $settings->addMediaFromRequest('hero_banner_image')->toMediaCollection('hero_banner');
        }

        return back()->with('success', 'Configuracion visual actualizada correctamente.');
    }
}
