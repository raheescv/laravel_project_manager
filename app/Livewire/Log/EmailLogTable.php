<?php

namespace App\Livewire\Log;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use Livewire\Component;
use Livewire\WithPagination;

class EmailLogTable extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $module = '';

    public $from_date = '';

    public $to_date = '';

    public $limit = 50;

    /** The log row open in the preview panel. */
    public $previewId;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'EmailLog-Refresh-Component' => '$refresh',
    ];

    public function mount(): void
    {
        $this->from_date = now()->subMonth()->format('Y-m-d');
        $this->to_date = now()->format('Y-m-d');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'status', 'module', 'from_date', 'to_date', 'limit'], true)) {
            $this->resetPage();
        }
    }

    public function preview($id): void
    {
        abort_unless(auth()->user()?->can('log.email'), 403);
        $this->previewId = $id;
    }

    public function closePreview(): void
    {
        $this->previewId = null;
    }

    public function getPreviewProperty(): ?EmailLog
    {
        return $this->previewId
            ? EmailLog::with('creator:id,name')->find($this->previewId)
            : null;
    }

    private function rows()
    {
        return EmailLog::query()
            ->when($this->search, function ($query, $value) {
                $query->where(function ($query) use ($value) {
                    $query->where('to_email', 'like', "%{$value}%")
                        ->orWhere('subject', 'like', "%{$value}%");
                });
            })
            ->when($this->status, fn ($query, $value) => $query->where('status', $value))
            ->when($this->module, fn ($query, $value) => $query->where('module', $value))
            ->when($this->from_date, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($this->to_date, fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->latest('id');
    }

    public function render()
    {
        $base = EmailLog::query()
            ->when($this->from_date, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($this->to_date, fn ($query, $value) => $query->whereDate('created_at', '<=', $value));

        return view('livewire.log.email-log-table', [
            'logs' => $this->rows()->paginate($this->limit),
            'modules' => EmailTemplate::modules(),
            'stats' => [
                'total' => (clone $base)->count(),
                'sent' => (clone $base)->where('status', 'sent')->count(),
                'queued' => (clone $base)->where('status', 'queued')->count(),
                'failed' => (clone $base)->where('status', 'failed')->count(),
            ],
        ]);
    }
}
