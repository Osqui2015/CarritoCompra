<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteAccount extends Component
{
  public string $password = '';

  public function deleteAccount(): void
  {
    $this->validate([
      'password' => ['required', 'current_password'],
    ]);

    /** @var User $user */
    $user = Auth::user();

    Auth::logout();

    $user->delete();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    $this->redirect('/', navigate: true);
  }

  public function render()
  {
    return view('livewire.profile.delete-account');
  }
}
