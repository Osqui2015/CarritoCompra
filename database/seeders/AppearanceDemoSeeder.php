<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Setting;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AppearanceDemoSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->seedBrandingAssets();
    $this->seedHeroBanners();
    $this->seedStoreSettings();
  }

  private function seedBrandingAssets(): void
  {
    Storage::disk('public')->makeDirectory('branding');

    $logoPath = 'branding/site-logo-demo.svg';
    $faviconPath = 'branding/site-favicon-demo.svg';

    Storage::disk('public')->put($logoPath, $this->logoSvg());
    Storage::disk('public')->put($faviconPath, $this->faviconSvg());

    Setting::put('site_name', 'TUS TECNOLOGIAS');
    Setting::put('site_logo', Storage::url($logoPath));
    Setting::put('site_favicon', Storage::url($faviconPath));
  }

  private function seedHeroBanners(): void
  {
    Storage::disk('public')->makeDirectory('banners/main_large');
    Storage::disk('public')->makeDirectory('banners/side_small');

    $mainPathA = 'banners/main_large/demo-main-1.svg';
    $mainPathB = 'banners/main_large/demo-main-2.svg';
    $sidePathA = 'banners/side_small/demo-side-1.svg';
    $sidePathB = 'banners/side_small/demo-side-2.svg';

    Storage::disk('public')->put($mainPathA, $this->mainBannerSvg('#7dd3fc', '#f0f9ff', 'Vuelta al Cole', 'Precios mayoristas para tu negocio.'));
    Storage::disk('public')->put($mainPathB, $this->mainBannerSvg('#fcd34d', '#ffedd5', 'Semana Tech', 'Combos y descuentos por volumen.'));
    Storage::disk('public')->put($sidePathA, $this->sideBannerSvg('#0f172a', '#1e293b', 'Smartwatch Pro Gen-X', 'Explorar ahora'));
    Storage::disk('public')->put($sidePathB, $this->sideBannerSvg('#f97316', '#fb923c', 'Envios a todo el pais', 'Cobertura inmediata'));

    Banner::query()->updateOrCreate(
      ['title' => 'Vuelta al Cole', 'type' => Banner::TYPE_MAIN_LARGE],
      [
        'subtitle' => 'Todo lo que necesitas para abastecer tu negocio.',
        'image_path' => $mainPathA,
        'link_url' => '/#productos-destacados',
        'is_active' => true,
        'sort_order' => 1,
        'active_from' => now()->subDay(),
        'active_to' => null,
      ],
    );

    Banner::query()->updateOrCreate(
      ['title' => 'Semana Tech', 'type' => Banner::TYPE_MAIN_LARGE],
      [
        'subtitle' => 'Laptops, accesorios y perifericos con promo semanal.',
        'image_path' => $mainPathB,
        'link_url' => '/#productos-destacados',
        'is_active' => true,
        'sort_order' => 2,
        'active_from' => now()->subDay(),
        'active_to' => null,
      ],
    );

    Banner::query()->updateOrCreate(
      ['title' => 'Smartwatch Pro Gen-X', 'type' => Banner::TYPE_SIDE_SMALL],
      [
        'subtitle' => 'Controla tu inventario desde cualquier lugar.',
        'image_path' => $sidePathA,
        'link_url' => '/#productos-destacados',
        'is_active' => true,
        'sort_order' => 1,
        'active_from' => now()->subDay(),
        'active_to' => null,
      ],
    );

    Banner::query()->updateOrCreate(
      ['title' => 'Envios a todo el pais', 'type' => Banner::TYPE_SIDE_SMALL],
      [
        'subtitle' => 'Logistica rapida y segura para tu negocio.',
        'image_path' => $sidePathB,
        'link_url' => '/#productos-destacados',
        'is_active' => true,
        'sort_order' => 2,
        'active_from' => now()->subDay(),
        'active_to' => null,
      ],
    );
  }

  private function seedStoreSettings(): void
  {
    $settings = StoreSetting::current();

    $settings->fill([
      'sales_whatsapp' => '5491112345678',
      'store_address' => 'Av. Comercial 1234, CABA',
      'business_hours' => 'Lun a Vie 09:00 a 18:00',
      'hero_banner_title' => 'Vuelta al Cole',
      'hero_banner_subtitle' => 'Todo lo que necesitas para abastecer tu negocio.',
      'hero_banner_link_type' => 'url',
      'hero_banner_link_value' => '/#productos-destacados',
    ])->save();
  }

  private function logoSvg(): string
  {
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="120" viewBox="0 0 320 120">
  <rect width="320" height="120" fill="#0f172a" rx="16"/>
  <rect x="18" y="18" width="84" height="84" fill="#f97316" rx="12"/>
  <text x="60" y="74" fill="#ffffff" font-family="Arial, sans-serif" font-size="28" text-anchor="middle" font-weight="700">TT</text>
  <text x="120" y="60" fill="#f8fafc" font-family="Arial, sans-serif" font-size="24" font-weight="700">TUS TECNOLOGIAS</text>
  <text x="120" y="84" fill="#cbd5e1" font-family="Arial, sans-serif" font-size="13">Mayorista</text>
</svg>
SVG;
  }

  private function faviconSvg(): string
  {
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256">
  <rect width="256" height="256" fill="#0f172a" rx="44"/>
  <rect x="34" y="34" width="188" height="188" fill="#f97316" rx="28"/>
  <text x="128" y="154" fill="#ffffff" font-family="Arial, sans-serif" font-size="86" text-anchor="middle" font-weight="700">TT</text>
</svg>
SVG;
  }

  private function mainBannerSvg(string $leftColor, string $rightColor, string $title, string $subtitle): string
  {
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="600" viewBox="0 0 1200 600">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0%" stop-color="{$leftColor}"/>
      <stop offset="100%" stop-color="{$rightColor}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="600" fill="url(#g)"/>
  <circle cx="940" cy="300" r="220" fill="#ffffff" opacity="0.18"/>
  <text x="90" y="240" fill="#0f172a" font-family="Arial, sans-serif" font-size="78" font-weight="700">{$title}</text>
  <text x="90" y="310" fill="#334155" font-family="Arial, sans-serif" font-size="34">{$subtitle}</text>
  <rect x="90" y="360" width="280" height="78" rx="20" fill="#f97316"/>
  <text x="230" y="410" fill="#ffffff" font-family="Arial, sans-serif" font-size="30" text-anchor="middle" font-weight="700">Ver Ofertas</text>
</svg>
SVG;
  }

  private function sideBannerSvg(string $bgA, string $bgB, string $title, string $subtitle): string
  {
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300" viewBox="0 0 600 300">
  <defs>
    <linearGradient id="s" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$bgA}"/>
      <stop offset="100%" stop-color="{$bgB}"/>
    </linearGradient>
  </defs>
  <rect width="600" height="300" fill="url(#s)"/>
  <circle cx="500" cy="70" r="80" fill="#ffffff" opacity="0.12"/>
  <text x="34" y="120" fill="#ffffff" font-family="Arial, sans-serif" font-size="44" font-weight="700">{$title}</text>
  <text x="34" y="170" fill="#e2e8f0" font-family="Arial, sans-serif" font-size="26">{$subtitle}</text>
</svg>
SVG;
  }
}
