<?php

namespace App\Livewire\Admin;

use App\Models\Banner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class AppearanceManager extends Component
{
    use WithFileUploads, WithPagination;

    public $title;
    public $subtitle;
    public $image;
    public $link;
    public $editingId = null;
    public $newImage;

    protected $rules = [
        'title' => 'required|string|max:255',
        'subtitle' => 'nullable|string|max:255',
        'link' => 'nullable|string|max:255',
        'newImage' => 'nullable|image|max:2048',
    ];

    public function resetForm()
    {
        $this->title = '';
        $this->subtitle = '';
        $this->link = '';
        $this->newImage = null;
        $this->editingId = null;
        $this->resetPage(); // Reset pagination when form is reset
    }

    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        $this->editingId = $banner->id;
        $this->title = $banner->title;
        $this->subtitle = $banner->subtitle;
        $this->link = $banner->link;
        $this->newImage = null;
    }

    public function save()
    {
        $this->validate();
        if ($this->editingId) {
            $banner = Banner::findOrFail($this->editingId);
        } else {
            $banner = new Banner();
        }
        $banner->title = $this->title;
        $banner->subtitle = $this->subtitle;
        $banner->link = $this->link;
        if ($this->newImage) {
            $banner->clearMediaCollection('banners');
            $banner->addMedia($this->newImage->getRealPath())
                ->toMediaCollection('banners');
        }
        $banner->save();
        $this->resetForm();
        session()->flash('success', 'Banner guardado correctamente.');
    }

    public function delete($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();
        session()->flash('success', 'Banner eliminado.');
        $this->resetPage(); // In case the last item on a page is deleted
    }

    public function render()
    {
        return view('livewire.admin.appearance-manager', [
            'banners' => Banner::orderByDesc('id')->paginate(5), // Paginate the results
        ]);
    }
}
