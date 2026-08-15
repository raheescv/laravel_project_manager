<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One outbound email, with the exact subject and body that were sent.
 */
class EmailLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'module',
        'type',
        'related_type',
        'related_id',
        'email_template_id',
        'to_email',
        'reply_to',
        'subject',
        'body',
        'status',
        'error',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function moduleLabel(): string
    {
        return EmailTemplate::moduleLabel($this->module);
    }

    public function typeLabel(): string
    {
        return EmailTemplate::typeLabelFor($this->module, $this->type);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'queued' => 'Queued',
            'sent' => 'Sent',
            'failed' => 'Failed',
            default => (string) $this->status,
        };
    }
}
