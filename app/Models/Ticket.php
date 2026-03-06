<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function rules(int $id = 0): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(self::statuses()))],
        ];
    }

    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when(! empty($filter['search']), function (Builder $q) use ($filter): void {
                $q->where(function (Builder $searchQ) use ($filter): void {
                    $searchQ->where('title', 'like', '%'.$filter['search'].'%')
                        ->orWhere('description', 'like', '%'.$filter['search'].'%');
                });
            })
            ->when(! empty($filter['status']), fn (Builder $q): Builder => $q->where('status', $filter['status']))
            ->when(! empty($filter['from_date']), fn (Builder $q): Builder => $q->whereDate('created_at', '>=', $filter['from_date']))
            ->when(! empty($filter['to_date']), fn (Builder $q): Builder => $q->whereDate('created_at', '<=', $filter['to_date']));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }
}
