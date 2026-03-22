<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'type', 'title', 'message', 'link', 'read', 'occurred_at',
    ];

    protected $casts = [
        'read'        => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a notification for a specific user.
     * @param int $tenantId
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string|null $link
     */
    public static function notify(
        int $tenantId,
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): void {
        try {
            static::create([
                'tenant_id'   => $tenantId,
                'user_id'     => $userId,
                'type'        => $type,
                'title'       => $title,
                'message'     => $message,
                'link'        => $link,
                'read'        => false,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never crash the main action
        }
    }

    /**
     * Create a notification for all admin users in a tenant.
     * Used when a clinician takes an action admins should know about.
     */
    public static function createForAdmins(
        int $tenantId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): void {
        $adminIds = User::where('tenant_id', $tenantId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->pluck('id');

        foreach ($adminIds as $adminId) {
            static::notify($tenantId, $adminId, $type, $title, $message, $link);
        }
    }

    /**
     * Create a notification for all users in a tenant except the actor.
     * @param int $tenantId
     * @param int $exceptUserId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string|null $link
     */
    public static function createForAllExcept(
        int $tenantId,
        int $exceptUserId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
    ): void {
        $userIds = User::where('tenant_id', $tenantId)
            ->where('id', '!=', $exceptUserId)
            ->where('is_active', true)
            ->pluck('id');

        foreach ($userIds as $userId) {
            static::notify($tenantId, $userId, $type, $title, $message, $link);
        }
    }
}
