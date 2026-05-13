<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratedBarcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'barcode_type_id',
        'plan_id',
        'source',
        'input_preview',
        'input_hash',
        'parameters',
        'export_format',
        'status',
        'generated_at',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barcodeType(): BelongsTo
    {
        return $this->belongsTo(BarcodeType::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(BarcodeExport::class);
    }
}
