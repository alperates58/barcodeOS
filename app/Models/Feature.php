<?php

namespace App\Models;

use LogicException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'value_type',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Feature $feature): void {
            if ($feature->planFeatures()->exists()) {
                throw new LogicException('Cannot delete a feature that is assigned to one or more plans.');
            }
        });
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_features')
            ->withPivot(['enabled', 'value', 'limit_value', 'metadata'])
            ->withTimestamps();
    }
}
