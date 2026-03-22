<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'client_id', 'uploaded_by', 'name', 'path', 'mime_type', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Human-readable size e.g. "1.2 MB"
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' MB';
        if ($bytes >= 1_024)     return round($bytes / 1_024, 1) . ' KB';
        return $bytes . ' B';
    }
}
