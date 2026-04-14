<?php

namespace App\Livewire\Log;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

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
}
