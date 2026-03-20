<?php

namespace App\Livewire\Admin;

use App\Models\Banner;
use App\Models\Setting;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class AppearanceManager extends Component
{
    use WithFileUploads;

    public ?int $editingBannerId = null;

    public string $bannerTitle = '';

    public string $bannerSubtitle = '';

    public string $bannerLinkUrl = '';

    public string $bannerType = Banner::TYPE_MAIN_LARGE;

    public bool $bannerIsActive = true;

    public int $bannerSortOrder = 1;

    public ?string $bannerActiveFrom = null;

    public ?string $bannerActiveTo = null;

    public mixed $bannerImage = null;

    public string $siteName = 'TUS TECNOLOGIAS';

    public string $salesWhatsapp = '';

    public string $storeAddress = '';

    public string $businessHours = '';

    public mixed $siteLogo = null;

    public mixed $siteFavicon = null;

    public bool $removeSiteLogo = false;

    public bool $removeSiteFavicon = false;

    public ?string $currentLogoUrl = null;

    public ?string $currentFaviconUrl = null;

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function saveBranding(): void
    {
        $validated = $this->validate([
            'siteName' => ['required', 'string', 'max:120'],
            'salesWhatsapp' => ['nullable', 'string', 'max:40'],
            'storeAddress' => ['nullable', 'string', 'max:160'],
            'businessHours' => ['nullable', 'string', 'max:200'],
            'siteLogo' => ['nullable', 'mimes:jpeg,png,jpg,svg', 'max:4096', 'dimensions:min_width=180,min_height=60'],
            'siteFavicon' => ['nullable', 'mimes:jpeg,png,jpg,svg', 'max:2048', 'dimensions:min_width=64,min_height=64'],
            'removeSiteLogo' => ['boolean'],
            'removeSiteFavicon' => ['boolean'],
        ]);

        $storeSetting = StoreSetting::current();
        $storeSetting->fill([
            'sales_whatsapp' => trim($validated['salesWhatsapp']),
            'store_address' => trim($validated['storeAddress']),
            'business_hours' => trim($validated['businessHours']),
        ])->save();

        Setting::put('site_name', trim($validated['siteName']));

        if ($this->removeSiteLogo && $this->currentLogoUrl) {
            $this->deletePublicUrl($this->currentLogoUrl);
            Setting::put('site_logo', null);
        }

        if ($this->removeSiteFavicon && $this->currentFaviconUrl) {
            $this->deletePublicUrl($this->currentFaviconUrl);
            Setting::put('site_favicon', null);
        }

        if ($this->siteLogo instanceof TemporaryUploadedFile) {
            if ($this->currentLogoUrl) {
                $this->deletePublicUrl($this->currentLogoUrl);
            }

            Setting::put('site_logo', $this->storeBrandingImage($this->siteLogo, 'branding/site-logo', 320, 120));
        }

        if ($this->siteFavicon instanceof TemporaryUploadedFile) {
            if ($this->currentFaviconUrl) {
                $this->deletePublicUrl($this->currentFaviconUrl);
            }

            Setting::put('site_favicon', $this->storeBrandingImage($this->siteFavicon, 'branding/site-favicon', 256, 256));
        }

        $this->reset('siteLogo', 'siteFavicon', 'removeSiteLogo', 'removeSiteFavicon');
        $this->loadSettings();

        session()->flash('success', 'Branding actualizado correctamente.');
    }

    public function saveBanner(): void
    {
        $minimums = $this->bannerType === Banner::TYPE_SIDE_SMALL
            ? ['width' => 500, 'height' => 250]
            : ['width' => 1000, 'height' => 500];

        $validated = $this->validate([
            'bannerTitle' => ['required', 'string', 'max:120'],
            'bannerSubtitle' => ['nullable', 'string', 'max:255'],
            'bannerLinkUrl' => ['nullable', 'string', 'max:255'],
            'bannerType' => ['required', 'in:' . Banner::TYPE_MAIN_LARGE . ',' . Banner::TYPE_SIDE_SMALL],
            'bannerIsActive' => ['boolean'],
            'bannerSortOrder' => ['required', 'integer', 'min:1', 'max:999'],
            'bannerActiveFrom' => ['nullable', 'date'],
            'bannerActiveTo' => ['nullable', 'date', 'after_or_equal:bannerActiveFrom'],
            'bannerImage' => array_filter([
                $this->editingBannerId === null ? 'required' : 'nullable',
                'image',
                'max:4096',
                'dimensions:min_width=' . $minimums['width'] . ',min_height=' . $minimums['height'],
            ]),
        ]);

        $banner = $this->editingBannerId !== null
            ? Banner::query()->findOrFail($this->editingBannerId)
            : new Banner();

        if ($this->bannerImage instanceof TemporaryUploadedFile) {
            if ($banner->image_path) {
                $this->deletePublicRelativePath($banner->image_path);
            }

            $banner->image_path = $this->storeBannerImage($this->bannerImage, $validated['bannerType']);
        }

        $banner->fill([
            'title' => trim($validated['bannerTitle']),
            'subtitle' => trim((string) ($validated['bannerSubtitle'] ?? '')) ?: null,
            'link_url' => trim((string) ($validated['bannerLinkUrl'] ?? '')) ?: null,
            'type' => $validated['bannerType'],
            'is_active' => (bool) $validated['bannerIsActive'],
            'sort_order' => (int) $validated['bannerSortOrder'],
            'active_from' => $validated['bannerActiveFrom'] ?: null,
            'active_to' => $validated['bannerActiveTo'] ?: null,
        ])->save();

        $this->resetBannerForm();

        session()->flash('success', 'Banner guardado correctamente.');
    }

    public function editBanner(int $bannerId): void
    {
        $banner = Banner::query()->findOrFail($bannerId);

        $this->editingBannerId = $banner->id;
        $this->bannerTitle = $banner->title;
        $this->bannerSubtitle = (string) ($banner->subtitle ?? '');
        $this->bannerLinkUrl = (string) ($banner->link_url ?? '');
        $this->bannerType = $banner->type;
        $this->bannerIsActive = $banner->is_active;
        $this->bannerSortOrder = $banner->sort_order;
        $this->bannerActiveFrom = $banner->active_from?->format('Y-m-d\TH:i');
        $this->bannerActiveTo = $banner->active_to?->format('Y-m-d\TH:i');
        $this->bannerImage = null;
        $this->resetErrorBag();
    }

    public function deleteBanner(int $bannerId): void
    {
        $banner = Banner::query()->findOrFail($bannerId);

        if ($banner->image_path) {
            $this->deletePublicRelativePath($banner->image_path);
        }

        $banner->delete();

        if ($this->editingBannerId === $bannerId) {
            $this->resetBannerForm();
        }

        session()->flash('success', 'Banner eliminado correctamente.');
    }

    public function resetBannerForm(): void
    {
        $this->reset(
            'editingBannerId',
            'bannerTitle',
            'bannerSubtitle',
            'bannerLinkUrl',
            'bannerImage',
            'bannerActiveFrom',
            'bannerActiveTo',
        );

        $this->bannerType = Banner::TYPE_MAIN_LARGE;
        $this->bannerIsActive = true;
        $this->bannerSortOrder = (int) (Banner::query()->max('sort_order') ?? 0) + 1;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.appearance-manager', [
            'banners' => Banner::query()->orderBy('type')->orderBy('sort_order')->orderBy('id')->get(),
            'mainBannerHint' => Banner::dimensionsForType(Banner::TYPE_MAIN_LARGE),
            'sideBannerHint' => Banner::dimensionsForType(Banner::TYPE_SIDE_SMALL),
        ]);
    }

    private function loadSettings(): void
    {
        $storeSetting = StoreSetting::current();

        $this->siteName = (string) Setting::value('site_name', 'TUS TECNOLOGIAS');
        $this->currentLogoUrl = Setting::value('site_logo');
        $this->currentFaviconUrl = Setting::value('site_favicon');
        $this->salesWhatsapp = (string) ($storeSetting->sales_whatsapp ?? '');
        $this->storeAddress = (string) ($storeSetting->store_address ?? '');
        $this->businessHours = (string) ($storeSetting->business_hours ?? '');
        $this->bannerSortOrder = (int) (Banner::query()->max('sort_order') ?? 0) + 1;
    }

    private function storeBannerImage(TemporaryUploadedFile $file, string $type): string
    {
        $dimensions = Banner::dimensionsForType($type);
        $relativePath = 'banners/' . $type . '/' . Str::uuid() . '.jpg';
        $absolutePath = Storage::disk('public')->path($relativePath);

        Storage::disk('public')->makeDirectory(dirname($relativePath));

        Image::load($file->getRealPath())
            ->fit(Fit::Crop, $dimensions['width'], $dimensions['height'])
            ->save($absolutePath);

        return $relativePath;
    }

    private function storeBrandingImage(TemporaryUploadedFile $file, string $directory, int $width, int $height): string
    {
        $extension = $file->getClientOriginalExtension();

        // Verificar si el archivo es SVG
        if (strtolower($extension) === 'svg') {
            $relativePath = $directory . '-' . Str::uuid() . '.svg';
            $absolutePath = Storage::disk('public')->path($relativePath);

            Storage::disk('public')->makeDirectory(dirname($relativePath));
            Storage::disk('public')->put($relativePath, file_get_contents($file->getRealPath()));

            return Storage::url($relativePath);
        }

        // Procesar otros formatos de imagen
        $relativePath = $directory . '-' . Str::uuid() . '.png';
        $absolutePath = Storage::disk('public')->path($relativePath);

        Storage::disk('public')->makeDirectory(dirname($relativePath));

        Image::load($file->getRealPath())
            ->fit(Fit::Crop, $width, $height)
            ->save($absolutePath);

        return Storage::url($relativePath);
    }

    private function deletePublicUrl(string $url): void
    {
        $relativePath = ltrim(Str::replaceFirst('/storage/', '', $url), '/');
        $this->deletePublicRelativePath($relativePath);
    }

    private function deletePublicRelativePath(string $relativePath): void
    {
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
