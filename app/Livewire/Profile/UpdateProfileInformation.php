<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UpdateProfileInformation extends Component
{
  public string $name = '';
  public string $email = '';
  public ?string $phone = null;
  public ?string $shipping_address = null;

  public function mount(): void
  {
    /** @var User $user */
    $user = Auth::user();

    $this->name = $user->name;
    $this->email = $user->email;
    $this->phone = $user->phone;
    $this->shipping_address = $user->shipping_address;
  }

  public function save(UserProfileService $service): void
  {
    /** @var User $user */
    $user = Auth::user();

    $validated = $this->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => [
        'required',
        'string',
        'lowercase',
        'email',
        'max:255',
        Rule::unique(User::class)->ignore($user->id),
      ],
      'phone' => ['nullable', 'string', 'max:30'],
      'shipping_address' => ['nullable', 'string', 'max:255'],
    ]);

    $service->updateProfile($user, $validated);

    session()->flash('profile_status', 'Perfil actualizado correctamente.');
  }

  public function render()
  {
    return view('livewire.profile.update-profile-information');
  }
}
