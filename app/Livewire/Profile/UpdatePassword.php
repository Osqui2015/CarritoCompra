<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class UpdatePassword extends Component
{
  public string $current_password = '';
  public string $password = '';
  public string $password_confirmation = '';

  public function save(UserProfileService $service): void
  {
    /** @var User $user */
    $user = Auth::user();

    $validated = $this->validate([
      'current_password' => ['required', 'current_password'],
      'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    $service->updatePassword($user, $validated['password']);

    $this->reset(['current_password', 'password', 'password_confirmation']);

    session()->flash('password_status', 'Contrasena actualizada correctamente.');
  }

  public function render()
  {
    return view('livewire.profile.update-password');
  }
}
