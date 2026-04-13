<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserProfileService
{
  /**
   * @param array{name:string,email:string,phone?:string|null,shipping_address?:string|null} $data
   */
  public function updateProfile(User $user, array $data): void
  {
    $user->fill([
      'name' => $data['name'],
      'email' => $data['email'],
      'phone' => $data['phone'] ?? null,
      'shipping_address' => $data['shipping_address'] ?? null,
    ]);

    if ($user->isDirty('email')) {
      $user->email_verified_at = null;
    }

    $user->save();
  }

  public function updatePassword(User $user, string $password): void
  {
    $user->password = $password;
    $user->save();
  }

  public function updatePreferences(User $user, array $preferences): void
  {
    $user->preferences = array_merge($this->defaultPreferences(), $preferences);
    $user->save();
  }

  public function updateAvatar(User $user, UploadedFile $avatar): void
  {
    $oldPath = $user->avatar_path;

    $newPath = $avatar->store('avatars', 'public');

    $user->avatar_path = $newPath;
    $user->save();

    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
      Storage::disk('public')->delete($oldPath);
    }
  }

  /**
   * @return array{email_notifications:bool,language:string}
   */
  public function defaultPreferences(): array
  {
    return [
      'email_notifications' => true,
      'language' => 'es',
    ];
  }
}
