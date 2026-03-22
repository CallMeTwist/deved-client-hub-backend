<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientInteraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'client_id', 'created_by',
        'type', 'summary', 'interacted_at', 'duration_minutes',
    ];

    protected $casts = [
        'interacted_at'    => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // "45 min" or null
    public function getFormattedDurationAttribute(): ?string
    {
        if (! $this->duration_minutes) return null;
        return $this->duration_minutes . ' min';
    }
}
