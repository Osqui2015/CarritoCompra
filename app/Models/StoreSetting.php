<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class StoreSetting extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\StoreSettingFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_name',
        'store_email',
        'store_phone',
        'store_whatsapp',
        'low_stock_threshold',
        'sales_whatsapp',
        'store_address',
        'business_hours',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'youtube_url',
        'hero_banner_title',
        'hero_banner_subtitle',
        'hero_banner_link_type',
        'hero_banner_link_value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'low_stock_threshold' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'low_stock_threshold' => 5,
                'hero_banner_title' => 'Vuelta al Cole',
                'hero_banner_subtitle' => 'Ofertas mayoristas para abastecer tu negocio.',
                'hero_banner_link_type' => 'url',
                'hero_banner_link_value' => '/',
                'store_name' => 'Nombre del Negocio',
                'store_email' => 'correo@ejemplo.com',
                'store_phone' => '1234567890',
                'store_whatsapp' => '1234567890',
                'store_address' => 'Dirección del negocio',
                'facebook_url' => 'https://facebook.com/tuempresa',
                'instagram_url' => 'https://instagram.com/tuempresa',
                'tiktok_url' => 'https://tiktok.com/@tuempresa',
                'youtube_url' => 'https://youtube.com/tuempresa',
            ],
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero_banner')->singleFile();
    }

    public function getHeroBannerUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('hero_banner');

        return $url !== '' ? $url : null;
    }
}
