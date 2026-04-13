<?php

namespace App\Livewire\Admin;

use App\Models\StoreSetting;
use Livewire\Component;
use Livewire\WithFileUploads;

class HeroBannerManager extends Component
{
  use WithFileUploads;

  public $hero_banner_title;
  public $hero_banner_subtitle;
  public $hero_banner_link_type;
  public $hero_banner_link_value;
  public $hero_banner_image;

  public function mount()
  {
    $settings = StoreSetting::current();
    $this->hero_banner_title = $settings->hero_banner_title;
    $this->hero_banner_subtitle = $settings->hero_banner_subtitle;
    $this->hero_banner_link_type = $settings->hero_banner_link_type;
    $this->hero_banner_link_value = $settings->hero_banner_link_value;
    $this->hero_banner_image = null;
  }

  public function save()
  {
    $this->validate([
      'hero_banner_title' => 'nullable|string|max:255',
      'hero_banner_subtitle' => 'nullable|string|max:255',
      'hero_banner_link_type' => 'nullable|string|max:20',
      'hero_banner_link_value' => 'nullable|string|max:255',
      'hero_banner_image' => 'nullable|image|max:4096',
    ]);

    $settings = StoreSetting::current();
    $settings->update([
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

    session()->flash('success', 'Banner principal actualizado.');
  }

  public function render()
  {
    $settings = StoreSetting::current();
    $bannerUrl = $settings->getFirstMediaUrl('hero_banner');

    return view('livewire.admin.hero-banner-manager', [
      'bannerUrl' => $bannerUrl,
    ]);
  }
}
