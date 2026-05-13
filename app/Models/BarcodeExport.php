<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarcodeExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_barcode_id',
        'user_id',
        'disk',
        'path',
        'filename',
        'mime_type',
        'format',
        'size',
        'checksum',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function generatedBarcode(): BelongsTo
    {
        return $this->belongsTo(GeneratedBarcode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
