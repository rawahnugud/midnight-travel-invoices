<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'company_name', 'tagline', 'address', 'phone', 'email', 'website',
        'logo_path', 'default_currency', 'tax_id',
    ];

    /**
     * Get the singleton business settings instance.
     */
    public static function get(): self
    {
        $settings = static::first();
        if (! $settings) {
            $settings = static::create([
                'company_name' => 'Midnight Travel',
                'tagline' => 'Where adventure meets luxury',
                'default_currency' => 'USD',
            ]);
        }
        return $settings;
    }

    /**
     * Logo URL for use in views (null if no logo).
     * Logos are stored in public/business/ so no storage:link is needed.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }
        return asset($this->logo_path);
    }
}
