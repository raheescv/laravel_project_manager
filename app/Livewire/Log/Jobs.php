<?php

namespace App\Livewire\Log;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use stdClass;

class Jobs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $queue = '';

    public int $limit = 25;

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    protected string $paginationTheme = 'bootstrap';

    public function updated(string $key, mixed $value): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'desc';
    }

    /**
     * Load and dispatch the payload details for a single job when its row is clicked.
     * Keeps the rendered HTML small by only fetching rich details on demand.
     */
    public function loadPayload(int $id): void
    {
        $item = DB::table('jobs')->where('id', $id)->first();

        if ($item === null) {
            $this->dispatch('job-payload-missing', id: $id);

            return;
        }

        $this->dispatch('job-payload-loaded', details: $this->payloadDetails($item));
    }

    public function resolveJobName(?string $payload): string
    {
        $decodedPayload = $this->decodePayload($payload);
        $jobName = data_get($decodedPayload, 'displayName')
            ?? data_get($decodedPayload, 'data.commandName')
            ?? data_get($decodedPayload, 'job');

        if (! is_string($jobName) || $jobName === '') {
            return 'Unknown Job';
        }

        return str_contains($jobName, '\\') ? class_basename($jobName) : $jobName;
    }

    public function resolveStatus(?int $reservedAt, int $availableAt, int $attempts): string
    {
        if ($reservedAt) {
            return 'Processing';
        }

        if ($availableAt > now()->timestamp) {
            return 'Delayed';
        }

        if ($attempts > 1) {
            return 'Retrying';
        }

        return 'Queued';
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'Processing' => 'bg-primary',
            'Delayed' => 'bg-warning text-dark',
            'Retrying' => 'bg-info text-dark',
            default => 'bg-secondary',
        };
    }

    public function formatTimestamp(?int $timestamp): ?string
    {
        if (! $timestamp) {
            return null;
        }

        return systemDateTime(Carbon::createFromTimestamp($timestamp));
    }

    /**
     * Build a structured representation of the job payload for the details modal.
     *
     * @return array<string, mixed>
     */
    public function payloadDetails(stdClass $item): array
    {
        $decoded = $this->decodePayload($item->payload);
        $status = $this->resolveStatus($item->reserved_at, $item->available_at, $item->attempts);

        $jobClass = data_get($decoded, 'data.commandName') ?? data_get($decoded, 'job');
        $prettyPayload = $decoded !== []
            ? (json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '')
            : (string) ($item->payload ?? '');

        $tags = data_get($decoded, 'tags', []);
        if (! is_array($tags)) {
            $tags = [];
        }

        $parsedCommand = $this->parseSerializedCommand(data_get($decoded, 'data.command'));
        $summary = $parsedCommand !== null ? $this->summarizeCommand($parsedCommand) : null;

        return [
            'id' => (int) $item->id,
            'jobName' => $this->resolveJobName($item->payload),
            'jobClass' => is_string($jobClass) ? $jobClass : null,
            'jobClassShort' => is_string($jobClass) && $jobClass !== '' ? class_basename($jobClass) : null,
            'displayName' => data_get($decoded, 'displayName'),
            'uuid' => data_get($decoded, 'uuid'),
            'queue' => (string) $item->queue,
            'attempts' => (int) $item->attempts,
            'status' => $status,
            'statusBadge' => $this->statusBadgeClass($status),
            'maxTries' => $this->stringifyValue(data_get($decoded, 'maxTries')),
            'maxExceptions' => $this->stringifyValue(data_get($decoded, 'maxExceptions')),
            'timeout' => $this->stringifyValue(data_get($decoded, 'timeout')),
            'backoff' => $this->stringifyValue(data_get($decoded, 'backoff')),
            'retryUntil' => $this->stringifyValue(data_get($decoded, 'retryUntil')),
            'tags' => array_values(array_filter(array_map(static fn ($tag): string => (string) $tag, $tags))),
            'createdAt' => $this->formatTimestamp($item->created_at),
            'availableAt' => $this->formatTimestamp($item->available_at),
            'reservedAt' => $this->formatTimestamp($item->reserved_at),
            'pretty' => $prettyPayload,
            'excerpt' => Str::limit((string) ($item->payload ?? ''), 120),
            'summary' => $summary,
        ];
    }

    public function render(): View
    {
        $currentTimestamp = now()->timestamp;

        $data = DB::table('jobs')
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);

                $query->where(function ($builder) use ($search): void {
                    if (is_numeric($search)) {
                        $builder->where('id', (int) $search)
                            ->orWhere('queue', 'like', "%{$search}%")
                            ->orWhere('payload', 'like', "%{$search}%");

                        return;
                    }

                    $builder->where('queue', 'like', "%{$search}%")
                        ->orWhere('payload', 'like', "%{$search}%");
                });
            })
            ->when($this->queue !== '', function ($query): void {
                $query->where('queue', $this->queue);
            })
            ->when($this->status !== '', function ($query) use ($currentTimestamp): void {
                match ($this->status) {
                    'queued' => $query->whereNull('reserved_at')
                        ->where('attempts', '<=', 1)
                        ->where('available_at', '<=', $currentTimestamp),
                    'processing' => $query->whereNotNull('reserved_at'),
                    'retrying' => $query->whereNull('reserved_at')
                        ->where('attempts', '>', 1),
                    'delayed' => $query->whereNull('reserved_at')
                        ->where('available_at', '>', $currentTimestamp),
                    default => null,
                };
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $queues = DB::table('jobs')
            ->distinct()
            ->orderBy('queue')
            ->pluck('queue');

        return view('livewire.log.jobs', [
            'data' => $data,
            'queues' => $queues,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodePayload(?string $payload): array
    {
        if (! is_string($payload) || $payload === '') {
            return [];
        }

        $decodedPayload = json_decode($payload, true);

        return is_array($decodedPayload) ? $decodedPayload : [];
    }

    protected function stringifyValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null;
    }

    /**
     * Safely unserialize the queued command string and convert all objects to plain arrays.
     *
     * @return array<string, mixed>|null
     */
    protected function parseSerializedCommand(mixed $command): ?array
    {
        if (! is_string($command) || $command === '') {
            return null;
        }

        set_error_handler(static fn (): bool => true);

        try {
            $value = unserialize($command, ['allowed_classes' => false]);
        } catch (\Throwable) {
            $value = false;
        } finally {
            restore_error_handler();
        }

        if ($value === false) {
            return null;
        }

        $normalized = $this->normalizeSerializedValue($value);

        return is_array($normalized) ? $normalized : null;
    }

    /**
     * Recursively convert `__PHP_Incomplete_Class` instances into plain arrays, preserving the class name.
     */
    protected function normalizeSerializedValue(mixed $value): mixed
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            $properties = (array) $value;
            $class = $properties['__PHP_Incomplete_Class_Name'] ?? 'UnknownClass';
            unset($properties['__PHP_Incomplete_Class_Name']);

            $clean = [
                '__class' => $class,
                '__classShort' => class_basename((string) $class),
            ];

            foreach ($properties as $key => $propertyValue) {
                $clean[$this->cleanSerializedPropertyName((string) $key)] = $this->normalizeSerializedValue($propertyValue);
            }

            return $clean;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $child) {
                $normalized[$key] = $this->normalizeSerializedValue($child);
            }

            return $normalized;
        }

        return $value;
    }

    /**
     * Serialized property names include null bytes for protected/private visibility; strip them.
     */
    protected function cleanSerializedPropertyName(string $key): string
    {
        if (! str_contains($key, "\0")) {
            return $key;
        }

        $parts = explode("\0", $key);

        return end($parts) ?: $key;
    }

    /**
     * Build a friendly summary of the parsed command, pulling out the most useful bits.
     *
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    protected function summarizeCommand(array $parsed): array
    {
        $class = is_string($parsed['__class'] ?? null) ? $parsed['__class'] : 'UnknownClass';

        $summary = [
            'class' => $class,
            'classShort' => class_basename($class),
            'type' => 'job',
            'properties' => [],
            'listener' => null,
            'event' => null,
            'model' => null,
            'changes' => [],
            'context' => null,
            'user' => null,
        ];

        if ($class === 'Illuminate\\Events\\CallQueuedListener') {
            $summary['type'] = 'listener';
            $summary['listener'] = [
                'class' => is_string($parsed['class'] ?? null) ? $parsed['class'] : null,
                'classShort' => is_string($parsed['class'] ?? null) ? class_basename($parsed['class']) : null,
                'method' => is_string($parsed['method'] ?? null) ? $parsed['method'] : 'handle',
            ];

            $eventData = $parsed['data'][0] ?? null;
            if (is_array($eventData)) {
                $this->hydrateEventSummary($summary, $eventData);
            }

            return $summary;
        }

        $summary['properties'] = $this->extractSimpleProperties($parsed);

        return $summary;
    }

    /**
     * Extract event + audit details into the summary structure.
     *
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $eventData
     */
    protected function hydrateEventSummary(array &$summary, array $eventData): void
    {
        $eventClass = is_string($eventData['__class'] ?? null) ? $eventData['__class'] : null;
        $summary['event'] = [
            'class' => $eventClass,
            'classShort' => $eventClass !== null ? class_basename($eventClass) : null,
        ];

        if ($eventClass !== 'OwenIt\\Auditing\\Events\\DispatchAudit') {
            $summary['properties'] = $this->extractSimpleProperties($eventData);

            return;
        }

        $modelClass = is_string($eventData['class'] ?? null) ? $eventData['class'] : null;
        $modelData = is_array($eventData['model_data'] ?? null) ? $eventData['model_data'] : [];
        $attributes = is_array($modelData['attributes'] ?? null) ? $modelData['attributes'] : [];
        $original = is_array($modelData['original'] ?? null) ? $modelData['original'] : [];

        $summary['model'] = [
            'class' => $modelClass,
            'classShort' => $modelClass !== null ? class_basename($modelClass) : null,
            'id' => $attributes['id'] ?? null,
            'event' => $modelData['auditEvent'] ?? null,
        ];

        $summary['changes'] = $this->diffAttributes($attributes, $original);

        $resolver = is_array($modelData['preloadedResolverData'] ?? null) ? $modelData['preloadedResolverData'] : [];

        if ($resolver !== []) {
            $summary['context'] = [
                'ipAddress' => is_string($resolver['ip_address'] ?? null) ? $resolver['ip_address'] : null,
                'userAgent' => is_string($resolver['user_agent'] ?? null) ? $resolver['user_agent'] : null,
                'url' => is_string($resolver['url'] ?? null) ? $resolver['url'] : null,
            ];

            $user = $resolver['user'] ?? null;
            if (is_array($user)) {
                $userClass = is_string($user['__class'] ?? null) ? $user['__class'] : null;
                $userAttrs = is_array($user['attributes'] ?? null) ? $user['attributes'] : [];

                $summary['user'] = [
                    'class' => $userClass,
                    'classShort' => $userClass !== null ? class_basename($userClass) : null,
                    'id' => $userAttrs['id'] ?? null,
                    'name' => is_string($userAttrs['name'] ?? null) ? $userAttrs['name'] : null,
                    'email' => is_string($userAttrs['email'] ?? null) ? $userAttrs['email'] : null,
                ];
            }
        }
    }

    /**
     * Build a list of field-level changes between current and original attribute arrays.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $original
     * @return array<int, array{field: string, old: string, new: string}>
     */
    protected function diffAttributes(array $attributes, array $original): array
    {
        $changes = [];
        $ignored = ['updated_at', 'created_at'];

        foreach ($attributes as $field => $newValue) {
            if (in_array($field, $ignored, true)) {
                continue;
            }

            $oldValue = $original[$field] ?? null;
            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = [
                'field' => (string) $field,
                'old' => $this->humanReadableValue($oldValue),
                'new' => $this->humanReadableValue($newValue),
            ];
        }

        return $changes;
    }

    /**
     * Extract scalar-ish properties from a parsed object array, skipping queue framework internals.
     *
     * @param  array<string, mixed>  $parsed
     * @return array<int, array{key: string, value: string}>
     */
    protected function extractSimpleProperties(array $parsed): array
    {
        $skip = [
            '__class', '__classShort',
            'job', 'connection', 'queue', 'delay', 'afterCommit',
            'chained', 'chainConnection', 'chainQueue', 'chainCatchCallbacks',
            'middleware', 'tries', 'maxTries', 'maxExceptions', 'backoff', 'retryUntil',
            'timeout', 'failOnTimeout', 'shouldBeEncrypted',
            'shouldBeUnique', 'shouldBeUniqueUntilProcessing', 'uniqueId', 'uniqueFor',
            'messageGroup', 'deduplicator', 'batchId',
        ];

        $properties = [];
        foreach ($parsed as $key => $value) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            $properties[] = [
                'key' => (string) $key,
                'value' => $this->humanReadableValue($value),
            ];
        }

        return $properties;
    }

    /**
     * Convert any normalized serialized value into a short, human-friendly string.
     */
    protected function humanReadableValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            $string = (string) $value;

            return $string === '' ? '—' : $string;
        }

        if (is_array($value)) {
            if (isset($value['__class'])) {
                $short = is_string($value['__classShort'] ?? null) ? $value['__classShort'] : 'Object';

                return $short.' (object)';
            }

            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return is_string($encoded) ? Str::limit($encoded, 200) : '—';
        }

        return '—';
    }
}
