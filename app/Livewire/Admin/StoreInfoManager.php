<?php

namespace App\Livewire\Admin;

use App\Models\StoreSetting;
use Livewire\Component;

class StoreInfoManager extends Component
{
    public $store_name;
    public $store_email;
    public $store_phone;
    public $store_whatsapp;
    public $store_address;
    public $facebook_url;
    public $instagram_url;
    public $tiktok_url;
    public $youtube_url;

    public $hero_banner_title;
    public $hero_banner_subtitle;
    public $hero_banner_link_type;
    public $hero_banner_link_value;
    public $hero_banner_image;

    public function mount()
    {
        $settings = StoreSetting::current();
        $this->store_name = $settings->store_name;
        $this->store_email = $settings->store_email;
        $this->store_phone = $settings->store_phone;
        $this->store_whatsapp = $settings->store_whatsapp;
        $this->store_address = $settings->store_address;
        $this->facebook_url = $settings->facebook_url;
        $this->instagram_url = $settings->instagram_url;
        $this->tiktok_url = $settings->tiktok_url;
        $this->youtube_url = $settings->youtube_url;
        $this->hero_banner_title = $settings->hero_banner_title;
        $this->hero_banner_subtitle = $settings->hero_banner_subtitle;
        $this->hero_banner_link_type = $settings->hero_banner_link_type;
        $this->hero_banner_link_value = $settings->hero_banner_link_value;
        $this->hero_banner_image = null;
    }

    public function save()
    {
        $this->validate([
            'store_name' => 'nullable|string|max:255',
            'store_email' => 'nullable|email|max:255',
            'store_phone' => 'nullable|string|max:30',
            'store_whatsapp' => 'nullable|string|max:30',
            'store_address' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'tiktok_url' => 'nullable|string|max:255',
            'youtube_url' => 'nullable|string|max:255',
            'hero_banner_title' => 'nullable|string|max:255',
            'hero_banner_subtitle' => 'nullable|string|max:255',
            'hero_banner_link_type' => 'nullable|string|max:20',
            'hero_banner_link_value' => 'nullable|string|max:255',
            'hero_banner_image' => 'nullable|image|max:4096',
        ]);

        $settings = StoreSetting::current();
        $settings->update([
            'store_name' => $this->store_name,
            'store_email' => $this->store_email,
            'store_phone' => $this->store_phone,
            'store_whatsapp' => $this->store_whatsapp,
            'store_address' => $this->store_address,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'tiktok_url' => $this->tiktok_url,
            'youtube_url' => $this->youtube_url,
            'hero_banner_title' => $this->hero_banner_title,
            'hero_banner_subtitle' => $this->hero_banner_subtitle,
            'hero_banner_link_type' => $this->hero_banner_link_type,
            'hero_banner_link_value' => $this->hero_banner_link_value,
        ]);

        if ($this->hero_banner_image) {
            $settings->clearMediaCollection('hero_banner');
            $settings->addMedia($this->hero_banner_image->getRealPath())
                ->usingFileName($this->hero_banner_image->getClientOriginalName())
                ->toMediaCollection('hero_banner');
        }

        session()->flash('success', 'Información del negocio actualizada.');
    }

    public function render()
    {
        $settings = StoreSetting::current();
        $bannerUrl = $settings->getFirstMediaUrl('hero_banner');
        return view('admin.store-info-manager', [
            'bannerUrl' => $bannerUrl,
        ]);
    }
}
