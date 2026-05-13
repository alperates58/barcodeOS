<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarcodeParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode_type_id',
        'label',
        'key',
        'type',
        'default_value',
        'min_value',
        'max_value',
        'options',
        'help_text',
        'is_required',
        'available_features',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'available_features' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function barcodeType(): BelongsTo
    {
        return $this->belongsTo(BarcodeType::class);
    }
}
