<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Record extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'client_id', 'template_key', 'template_version',
        'data', 'notes', 'status', 'recorded_at', 'created_by', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'data'             => 'array',
        'recorded_at'      => 'datetime',
        'reviewed_at'      => 'datetime',
        'template_version' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Resolve the Template snapshot used when this record was created.
     * Useful for re-rendering historical record data with the correct schema.
     */
    public function template(): ?Template
    {
        return Template::where('tenant_id', $this->tenant_id)
            ->where('key', $this->template_key)
            ->where('version', $this->template_version)
            ->first();
    }
}
