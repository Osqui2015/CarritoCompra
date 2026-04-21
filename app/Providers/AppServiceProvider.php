<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        View::composer('*', function ($view): void {
            $view->with('branding', $this->brandingPayload());
        });

        Inertia::share('branding', fn(): array => $this->brandingPayload());
    }

    /**
     * @return array<string, string|null>
     */
    private function brandingPayload(): array
    {
        $defaultLogo = file_exists(public_path('branding/logo.jpg'))
            ? asset('branding/logo.jpg')
            : asset('branding/logo.svg');

        if (! Schema::hasTable('settings')) {
            return [
                'site_logo' => $defaultLogo,
                'site_favicon' => $defaultLogo,
                'site_name' => config('app.name', 'TJ'),
            ];
        }

        return Setting::branding();
    }
}

