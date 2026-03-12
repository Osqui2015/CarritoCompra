<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
  use HasFactory;

  public const TYPE_MAIN_LARGE = 'main_large';

  public const TYPE_SIDE_SMALL = 'side_small';

  /**
   * @var list<string>
   */
  protected $fillable = [
    'title',
    'subtitle',
    'image_path',
    'link_url',
    'type',
    'is_active',
    'sort_order',
    'active_from',
    'active_to',
  ];

  /**
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
      'sort_order' => 'integer',
      'active_from' => 'datetime',
      'active_to' => 'datetime',
    ];
  }

  public function scopeCurrentlyVisible(Builder $query): Builder
  {
    return $query
      ->where('is_active', true)
      ->where(function (Builder $innerQuery): void {
        $innerQuery->whereNull('active_from')
          ->orWhere('active_from', '<=', now());
      })
      ->where(function (Builder $innerQuery): void {
        $innerQuery->whereNull('active_to')
          ->orWhere('active_to', '>=', now());
      });
  }

  public function getImageUrlAttribute(): ?string
  {
    if ($this->image_path === null || $this->image_path === '') {
      return null;
    }

    return Storage::disk('public')->url($this->image_path);
  }

  public static function dimensionsForType(string $type): array
  {
    return $type === self::TYPE_SIDE_SMALL
      ? ['width' => 600, 'height' => 300, 'label' => '600x300 px']
      : ['width' => 1200, 'height' => 600, 'label' => '1200x600 px'];
  }
}
