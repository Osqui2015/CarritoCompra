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

    public function mount()
    {
        $settings = StoreSetting::current();
        $this->store_name = $settings->store_name;
        $this->store_email = $settings->store_email;
        $this->store_phone = $settings->store_phone;
        $this->store_whatsapp = $settings->store_whatsapp;
        $this->store_address = $settings->store_address;
    }

    public function save()
    {
        $this->validate([
            'store_name' => 'nullable|string|max:255',
            'store_email' => 'nullable|email|max:255',
            'store_phone' => 'nullable|string|max:30',
            'store_whatsapp' => 'nullable|string|max:30',
            'store_address' => 'nullable|string|max:255',
        ]);

        $settings = StoreSetting::current();
        $settings->update([
            'store_name' => $this->store_name,
            'store_email' => $this->store_email,
            'store_phone' => $this->store_phone,
            'store_whatsapp' => $this->store_whatsapp,
            'store_address' => $this->store_address,
        ]);

        session()->flash('success', 'Información del negocio actualizada.');
    }

    public function render()
    {
        return view('admin.store-info-manager');
    }
}
