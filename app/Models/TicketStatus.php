<?php

namespace App\Models;

use App\Services\TenantService;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A board column of the ticket console. Each tenant shapes its own board; the
 * `key` is what `tickets.status` stores and never changes after creation, so a
 * rename only touches `name`.
 */
class TicketStatus extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const MAX_COLUMNS = 12;

    /** The board a tenant starts with. */
    public const DEFAULTS = [
        ['key' => Ticket::STATUS_OPEN, 'name' => 'Open', 'icon' => 'fa-inbox', 'color' => '#6b93c9'],
        ['key' => Ticket::STATUS_IN_PROGRESS, 'name' => 'In Progress', 'icon' => 'fa-spinner', 'color' => '#c9a35b'],
        ['key' => Ticket::STATUS_RESOLVED, 'name' => 'Resolved', 'icon' => 'fa-check-circle', 'color' => '#6aa88a'],
        ['key' => Ticket::STATUS_CLOSED, 'name' => 'Closed', 'icon' => 'fa-archive', 'color' => '#929aa6'],
    ];

    protected $fillable = [
        'tenant_id',
        'key',
        'name',
        'color',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * The current tenant's columns in board order. A tenant that has never
     * opened the board gets the default four on first read.
     *
     * @return Collection<int, self>
     */
    public static function ordered(): Collection
    {
        $statuses = self::query()->orderBy('sort_order')->orderBy('id')->get();
        if ($statuses->isNotEmpty()) {
            return $statuses;
        }

        $tenantId = app(TenantService::class)->getCurrentTenantId();
        if (! $tenantId) {
            return $statuses;
        }

        self::query()->insertOrIgnore(collect(self::DEFAULTS)->map(fn (array $status, int $i): array => $status + [
            'tenant_id' => $tenantId,
            'sort_order' => $i,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        return self::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    /** The column a new ticket lands in when none is picked: the first one. */
    public static function defaultKey(): string
    {
        return self::ordered()->first()->key ?? Ticket::STATUS_OPEN;
    }

    /** A stable key for a new column, made unique within the tenant. */
    public static function keyFor(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 40, '') ?: 'status';
        $key = $base;
        for ($n = 2; self::query()->where('key', $key)->exists(); $n++) {
            $key = "{$base}_{$n}";
        }

        return $key;
    }
}
