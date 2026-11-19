<?php

namespace App\Livewire\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    /**
     * The audits table has no tenant column, so audits are shown only when
     * the audited record itself resolves through its model's tenant scopes.
     */
    protected function auditedRecordIsVisible(): bool
    {
        $class = 'App\\Models\\'.$this->model;
        if (! is_string($this->model) || ! preg_match('/^[A-Za-z]+$/', $this->model) || ! is_subclass_of($class, Model::class)) {
            return false;
        }

        $query = $class::query();
        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->whereKey($this->table_id)->exists();
    }

    public function render()
    {
        // @phpstan-ignore larastan.relationExistence (OwenIt Audit::user() is an untyped morphTo Larastan cannot see)
        $base = Audit::with('user')
            ->where('auditable_type', 'App\\Models\\'.$this->model)
            ->where('auditable_id', $this->table_id)
            ->when(! $this->auditedRecordIsVisible(), fn ($query) => $query->whereRaw('1 = 0'));

        $all = (clone $base)->latest()->get();

        $filtered = $all->when($this->event !== '', fn ($c) => $c->where('event', $this->event))
            ->when($this->user_id !== '', fn ($c) => $c->where('user_id', $this->user_id))
            ->when($this->date_from !== '', fn ($c) => $c->filter(fn ($a) => optional($a->getAttribute('created_at'))->toDateString() >= $this->date_from))
            ->when($this->date_to !== '', fn ($c) => $c->filter(fn ($a) => optional($a->getAttribute('created_at'))->toDateString() <= $this->date_to))
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
        $users = $all->map(fn ($a) => ['id' => $a->getAttribute('user_id'), 'name' => $a->user->name ?? 'System'])
            ->unique('id')
            ->values();

        $first = $all->last();
        $last = $all->first();

        $stats = [
            'total' => $all->count(),
            'shown' => $filtered->count(),
            'first_at' => $first?->getAttribute('created_at'),
            'last_at' => $last?->getAttribute('created_at'),
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
