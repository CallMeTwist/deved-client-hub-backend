<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'description', 'link', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log an activity. Call this anywhere an action is taken.
     */
    public static function log(
        int $tenantId,
        int $userId,
        string $type,
        string $description,
        ?string $link = null,
    ): void {
        try {
            static::create([
                'tenant_id'   => $tenantId,
                'user_id'     => $userId,
                'type'        => $type,
                'description' => $description,
                'link'        => $link,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let activity logging crash the main action
        }
    }
}
