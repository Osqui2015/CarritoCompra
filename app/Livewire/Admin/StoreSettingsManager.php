<?php

namespace App\Livewire\Admin;

use App\Models\StoreSetting;
use Livewire\Component;

class StoreSettingsManager extends Component
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
        ]);

        session()->flash('success', 'Datos del negocio actualizados.');
    }

    public function render()
    {
        return view('livewire.admin.store-settings-manager');
    }
}
