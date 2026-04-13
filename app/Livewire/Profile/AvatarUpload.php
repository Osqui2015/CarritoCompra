<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class AvatarUpload extends Component
{
  use WithFileUploads;

  public $avatar;

  public function save(UserProfileService $service): void
  {
    /** @var User $user */
    $user = Auth::user();

    $validated = $this->validate([
      'avatar' => ['required', 'image', 'max:2048'],
    ]);

    $service->updateAvatar($user, $validated['avatar']);

    $this->reset('avatar');

    session()->flash('avatar_status', 'Foto de perfil actualizada correctamente.');
  }

  public function render()
  {
    return view('livewire.profile.avatar-upload', [
      'avatarUrl' => Auth::user()?->avatar_url,
    ]);
  }
}
