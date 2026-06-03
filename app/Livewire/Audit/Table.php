<?php

namespace App\Livewire\Audit;

use Livewire\Component;
use OwenIt\Auditing\Models\Audit;

class Table extends Component
{
    public $model;

    public $table_id;

    public $view = 'timeline';

    public $event = '';

    public $user_id = '';

    public $date_from = '';

    public $date_to = '';

    public $search = '';

    public function modal($model, $table_id)
    {
        $this->model = $model;
        $this->table_id = $table_id;
    }

    public function resetFilters(): void
    {
        $this->event = '';
        $this->user_id = '';
        $this->date_from = '';
        $this->date_to = '';
        $this->search = '';
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['timeline', 'table'], true) ? $view : 'timeline';
    }

    public function render()
    {
        $base = Audit::with('user')
            ->where('auditable_type', 'App\\Models\\'.$this->model)
            ->where('auditable_id', $this->table_id);

        $all = (clone $base)->latest()->get();

        $filtered = $all->when($this->event !== '', fn ($c) => $c->where('event', $this->event))
            ->when($this->user_id !== '', fn ($c) => $c->where('user_id', $this->user_id))
            ->when($this->date_from !== '', fn ($c) => $c->filter(fn ($a) => optional($a->created_at)->toDateString() >= $this->date_from))
            ->when($this->date_to !== '', fn ($c) => $c->filter(fn ($a) => optional($a->created_at)->toDateString() <= $this->date_to))
            ->when($this->search !== '', function ($c) {
                $needle = mb_strtolower($this->search);

                return $c->filter(function ($a) use ($needle) {
                    $old = is_array($a->old_values ?? null) ? $a->old_values : (array) ($a->old_values ?? []);
                    $new = is_array($a->new_values ?? null) ? $a->new_values : (array) ($a->new_values ?? []);
                    $haystack = mb_strtolower(implode(' ', array_merge(array_keys($old + $new), array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), array_values($old + $new)))));

                    return str_contains($haystack, $needle);
                });
            })
            ->values();

        $events = $all->pluck('event')->unique()->filter()->values();
        $users = $all->map(fn ($a) => ['id' => $a->user_id, 'name' => $a->user?->name ?? 'System'])
            ->unique('id')
            ->values();

        $first = $all->last();
        $last = $all->first();

        $stats = [
            'total' => $all->count(),
            'shown' => $filtered->count(),
            'first_at' => $first?->created_at,
            'last_at' => $last?->created_at,
            'contributors' => $users,
        ];

        return view('livewire.audit.table', [
            'audits' => $filtered,
            'allAudits' => $all,
            'stats' => $stats,
            'events' => $events,
            'users' => $users,
        ]);
    }
}
