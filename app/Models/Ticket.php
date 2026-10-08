<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    /** Filter value for tickets that have no group. */
    public const NO_GROUP = '__none';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'status',
        'group',
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
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(self::statuses()))],
            'group' => ['nullable', 'string', 'max:100'],
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
            ->when(($filter['group'] ?? '') === self::NO_GROUP, fn (Builder $q): Builder => $q->whereNull('group'))
            ->when(! in_array($filter['group'] ?? '', ['', self::NO_GROUP], true), fn (Builder $q): Builder => $q->where('group', $filter['group']))
            ->when(! empty($filter['from_date']), fn (Builder $q): Builder => $q->whereDate('created_at', '>=', $filter['from_date']))
            ->when(! empty($filter['to_date']), fn (Builder $q): Builder => $q->whereDate('created_at', '<=', $filter['to_date']));
    }

    /**
     * Distinct group names in use, for the filter strip and the field's suggestions.
     *
     * @return list<string>
     */
    public static function groupNames(): array
    {
        return self::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group')->all();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /** @return HasMany<TicketComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }
}
