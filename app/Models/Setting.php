<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
  use HasFactory;

  /**
   * @var list<string>
   */
  protected $fillable = [
    'key',
    'value',
  ];

  public static function value(string $key, mixed $default = null): mixed
  {
    return static::allAsArray()[$key] ?? $default;
  }

  /**
   * @return array<string, string|null>
   */
  public static function allAsArray(): array
  {
    if (! Schema::hasTable('settings')) {
      return [];
    }

    /** @var array<string, string|null> $settings */
    $settings = Cache::remember('settings.all', now()->addMinutes(10), function (): array {
      return static::query()
        ->pluck('value', 'key')
        ->map(fn($value) => $value !== null ? (string) $value : null)
        ->all();
    });

    return $settings;
  }

  /**
   * @return array<string, string|null>
   */
  public static function branding(): array
  {
    return [
      'site_logo' => static::value('site_logo'),
      'site_favicon' => static::value('site_favicon'),
      'site_name' => static::value('site_name', config('app.name', 'TUS TECNOLOGIAS')),
    ];
  }

  public static function put(string $key, ?string $value): self
  {
    $setting = static::query()->updateOrCreate(
      ['key' => $key],
      ['value' => $value],
    );

    static::forgetCache();

    return $setting;
  }

  public static function forgetCache(): void
  {
    Cache::forget('settings.all');
  }
}
