<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember('setting:'.$key, now()->addHour(), function () use ($key) {
            return Setting::find($key)?->value;
        });

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('setting:'.$key);
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Setting::all()
            ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->value])
            ->toArray();
    }

    public static function forget(string $key): void
    {
        Cache::forget('setting:'.$key);
    }
}
