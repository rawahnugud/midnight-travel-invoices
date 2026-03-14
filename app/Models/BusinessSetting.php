<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'company_name', 'tagline', 'address', 'phone', 'email', 'website',
        'logo_path', 'default_currency', 'tax_id',
        'primary_color', 'accent_color', 'login_logo_path', 'invoice_header_color',
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

    /**
     * Login page logo URL. Uses login_logo_path if set, otherwise company logo.
     */
    public function getLoginLogoUrlAttribute(): ?string
    {
        if ($this->login_logo_path) {
            return asset($this->login_logo_path);
        }
        return $this->logo_url;
    }

    /** Primary theme color for app and optionally invoice (hex). */
    public function getPrimaryColorAttribute($value): string
    {
        return $value ?: '#85144b';
    }

    /** Accent theme color (hex). */
    public function getAccentColorAttribute($value): string
    {
        return $value ?: '#c9a227';
    }

    /** Color for printed invoice header/table (hex). Falls back to primary_color. */
    public function getInvoiceHeaderColorAttribute($value): string
    {
        if ($value) {
            return $value;
        }
        return $this->primary_color;
    }
}
