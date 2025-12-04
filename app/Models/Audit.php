<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use BackedEnum;

class Audit extends Model
{
    use HasFactory;

    /**
     * This table stores only a created_at timestamp (no updated_at column).
     * Disable automatic timestamps to avoid attempts to write updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $table = 'audits';

    protected $fillable = [
        'user_id',
        'user_email',
        'auditable_type',
        'auditable_id',
        'action',
        'metadata',
        'ip_address',
        'user_agent',
        'result',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Create an audit log entry.
     *
     * Usage: Audit::log(AuditAction::SHARE_ACCESSED, $share, $userId, $userEmail, ['foo'=>'bar']);
     *
     * @param  mixed  $action  Enum or string
     * @param  \Illuminate\Database\Eloquent\Model|null  $auditable
     * @param  int|null  $userId
     * @param  string|null  $userEmail
     * @param  array|null  $metadata
     * @param  string  $result
     * @param  string|null  $errorMessage
     * @return static
     */
    public static function log(mixed $action, $auditable = null, ?int $userId = null, ?string $userEmail = null, ?array $metadata = [], string $result = 'success', ?string $errorMessage = null)
    {
        // Normalize action (if enum provided - BackedEnum)
        if (is_object($action) && $action instanceof BackedEnum) {
            $actionValue = $action->value;
        } else {
            $actionValue = (string) $action;
        }

        // Determine user if not passed
        if (is_null($userId) && function_exists('auth') && $user = auth()->user()) {
            $userId = $user->id;
            $userEmail = $userEmail ?? $user->email ?? null;
        }

        // Auditable polymorphic
        $auditableType = null;
        $auditableId = null;
        if ($auditable && is_object($auditable) && method_exists($auditable, 'getKey')) {
            $auditableType = $auditable->getMorphClass();
            $auditableId = $auditable->getKey();
        }

        // Request context (if available)
        $ip = null;
        $ua = null;
        if (function_exists('request')) {
            try {
                $req = request();
                $ip = $req->ip();
                $ua = $req->userAgent();
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $data = [
            'user_id' => $userId,
            'user_email' => $userEmail,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'action' => $actionValue,
            'metadata' => $metadata ? Arr::wrap($metadata) : null,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'result' => $result,
            'error_message' => $errorMessage,
        ];

        return static::create($data);
    }
}
