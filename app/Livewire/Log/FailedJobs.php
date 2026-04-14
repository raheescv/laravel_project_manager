<?php

namespace App\Livewire\Log;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class FailedJobs extends Component
{
    use WithPagination;

    public string $search = '';

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

    public function failureMessage(?string $exception): string
    {
        if (! is_string($exception) || trim($exception) === '') {
            return 'No exception message recorded.';
        }

        $lines = preg_split('/\\r\\n|\\r|\\n/', trim($exception));

        return trim($lines[0] ?? 'No exception message recorded.');
    }

    public function render(): View
    {
        $data = DB::table('failed_jobs')
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);

                $query->where(function ($builder) use ($search): void {
                    if (is_numeric($search)) {
                        $builder->where('id', (int) $search)
                            ->orWhere('uuid', 'like', "%{$search}%")
                            ->orWhere('queue', 'like', "%{$search}%")
                            ->orWhere('payload', 'like', "%{$search}%")
                            ->orWhere('exception', 'like', "%{$search}%");

                        return;
                    }

                    $builder->where('uuid', 'like', "%{$search}%")
                        ->orWhere('queue', 'like', "%{$search}%")
                        ->orWhere('payload', 'like', "%{$search}%")
                        ->orWhere('exception', 'like', "%{$search}%");
                });
            })
            ->when($this->queue !== '', function ($query): void {
                $query->where('queue', $this->queue);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $queues = DB::table('failed_jobs')
            ->distinct()
            ->orderBy('queue')
            ->pluck('queue');

        return view('livewire.log.failed-jobs', [
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
