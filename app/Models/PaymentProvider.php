<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_enabled',
        'mode',
        'public_config',
        'secret_config',
        'metadata',
    ];

    protected $appends = [
        'environment_enabled',
        'environment_configured',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'public_config' => 'array',
            'secret_config' => 'array',
            'metadata' => 'array',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getEnvironmentEnabledAttribute(): bool
    {
        return (bool) data_get(config('barcodeos.payment_providers'), "{$this->slug}.enabled", false);
    }

    public function getEnvironmentConfiguredAttribute(): bool
    {
        $credentials = data_get(config('barcodeos.payment_providers'), "{$this->slug}.credentials", []);

        if ($credentials === []) {
            return false;
        }

        foreach ($credentials as $value) {
            if (blank($value)) {
                return false;
            }
        }

        return true;
    }
}
