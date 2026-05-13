<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarcodeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode_category_id',
        'name',
        'slug',
        'description',
        'status',
        'icon',
        'example_value',
        'validation_rules',
        'default_width',
        'default_height',
        'default_margin',
        'default_format',
        'supported_export_formats',
        'required_features',
        'parameter_schema',
        'documentation',
        'seo_title',
        'seo_description',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'validation_rules' => 'array',
            'default_width' => 'integer',
            'default_height' => 'integer',
            'default_margin' => 'integer',
            'supported_export_formats' => 'array',
            'required_features' => 'array',
            'parameter_schema' => 'array',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BarcodeCategory::class, 'barcode_category_id');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(BarcodeParameter::class);
    }

    public function generatedBarcodes(): HasMany
    {
        return $this->hasMany(GeneratedBarcode::class);
    }
}
