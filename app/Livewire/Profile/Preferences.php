<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Preferences extends Component
{
  public bool $email_notifications = true;
  public string $language = 'es';

  public function mount(UserProfileService $service): void
  {
    /** @var User $user */
    $user = Auth::user();

    $preferences = array_merge(
      $service->defaultPreferences(),
      is_array($user->preferences) ? $user->preferences : []
    );

    $this->email_notifications = (bool) $preferences['email_notifications'];
    $this->language = (string) $preferences['language'];
  }

  public function save(UserProfileService $service): void
  {
    /** @var User $user */
    $user = Auth::user();

    $validated = $this->validate([
      'email_notifications' => ['required', 'boolean'],
      'language' => ['required', 'string', 'in:es,en'],
    ]);

    $service->updatePreferences($user, $validated);

    session()->flash('preferences_status', 'Preferencias actualizadas correctamente.');
  }

  public function render()
  {
    return view('livewire.profile.preferences');
  }
}
