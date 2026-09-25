<?php

namespace App\Livewire\Report\Sale;

use App\Livewire\Concerns\HasReportPeriod;
use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * /report/sale_feedback — the star rating, type and comment customers left on
 * their completed sales, with the rating spread and response rate for the period.
 *
 * A sale counts as "feedback" only when it carries a rating or a comment: the
 * sale form pre-fills `feedback_type` with "compliment", so the type alone says
 * nothing about whether the customer was ever asked.
 */
class FeedbackReport extends Component
{
    use HasReportPeriod;
    use WithPagination;

    public const SORTABLE = ['sales.date', 'sales.invoice_no', 'sales.rating', 'sales.grand_total'];

    public $search = '';

    public $branch_id = '';

    public $rating = '';

    public $feedback_type = '';

    /** Only feedback that came with a written comment. */
    public $comments_only = false;

    public $from_date;

    public $to_date;

    public $perPage = 25;

    public $sortField = 'sales.date';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->branch_id = session('branch_id');
        $this->setRange('this_month');
    }

    public function updated($key): void
    {
        if ($key !== 'perPage') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'rating', 'feedback_type', 'comments_only']);
        $this->branch_id = session('branch_id');
        $this->setRange('this_month');
    }

    /** Toggle a star filter from the distribution bars; a second tap clears it. */
    public function filterRating($value): void
    {
        $this->rating = (string) $this->rating === (string) $value ? '' : (string) $value;
        $this->resetPage();
    }

    public function sortBy($field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->sortField = $field;
    }

    /**
     * @return array{search: string, branch_id: mixed, rating: mixed, feedback_type: string, comments_only: bool, from_date: ?string, to_date: ?string}
     */
    public function filters(): array
    {
        return [
            'search' => (string) $this->search,
            'branch_id' => $this->branch_id,
            'rating' => $this->rating,
            'feedback_type' => (string) $this->feedback_type,
            'comments_only' => (bool) $this->comments_only,
            'from_date' => $this->from_date ?: null,
            'to_date' => $this->to_date ?: null,
        ];
    }

    /** Completed sales in the period and branch, before any feedback narrowing. */
    public static function salesQuery(array $filters): Builder
    {
        return Sale::query()
            ->where('sales.status', 'completed')
            ->when($filters['branch_id'] ?? '', fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($filters['from_date'] ?? null, fn ($q, $value) => $q->whereDate('sales.date', '>=', $value))
            ->when($filters['to_date'] ?? null, fn ($q, $value) => $q->whereDate('sales.date', '<=', $value));
    }

    /** Sales that carry a rating or a written comment. */
    public static function feedbackQuery(array $filters): Builder
    {
        return self::salesQuery($filters)->where(function ($q): void {
            $q->where('sales.rating', '>', 0)
                ->orWhere(fn ($inner) => $inner->whereNotNull('sales.feedback')->where('sales.feedback', '!=', ''));
        });
    }

    /** The feedback rows on screen: the feedback set plus the rail's narrowing filters. */
    public static function filteredQuery(array $filters): Builder
    {
        return self::feedbackQuery($filters)
            ->when($filters['rating'] ?? '', fn ($q, $value) => $q->where('sales.rating', (int) $value))
            ->when($filters['feedback_type'] ?? '', fn ($q, $value) => $q->where('sales.feedback_type', $value))
            ->when($filters['comments_only'] ?? false, fn ($q) => $q->whereNotNull('sales.feedback')->where('sales.feedback', '!=', ''))
            ->when(trim($filters['search'] ?? ''), function ($q, $value): void {
                $q->where(function ($inner) use ($value): void {
                    $inner->where('sales.invoice_no', 'like', "%{$value}%")
                        ->orWhere('sales.customer_name', 'like', "%{$value}%")
                        ->orWhere('sales.customer_mobile', 'like', "%{$value}%")
                        ->orWhere('sales.feedback', 'like', "%{$value}%")
                        ->orWhereHas('account', fn ($account) => $account->where('name', 'like', "%{$value}%"));
                });
            });
    }

    /**
     * Headline figures over the whole feedback set for the period and branch —
     * deliberately ignoring the rating/type/search narrowing, so the distribution
     * bars keep showing the full picture while one of them is selected.
     *
     * @return array{sales: int, responses: int, rated: int, average: float, comments: int, stars: array<int, int>, types: array<string, int>}
     */
    public static function summary(array $filters): array
    {
        $row = self::feedbackQuery($filters)
            ->reorder()
            ->selectRaw('COUNT(*) as responses')
            ->selectRaw('SUM(CASE WHEN sales.rating > 0 THEN 1 ELSE 0 END) as rated')
            ->selectRaw('AVG(CASE WHEN sales.rating > 0 THEN sales.rating END) as average')
            ->selectRaw("SUM(CASE WHEN sales.feedback IS NOT NULL AND sales.feedback != '' THEN 1 ELSE 0 END) as comments")
            ->first();

        $stars = self::feedbackQuery($filters)
            ->reorder()
            ->where('sales.rating', '>', 0)
            ->groupBy('sales.rating')
            ->select('sales.rating', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'rating');

        $types = self::feedbackQuery($filters)
            ->reorder()
            ->whereNotNull('sales.feedback_type')
            ->groupBy('sales.feedback_type')
            ->select('sales.feedback_type', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'feedback_type');

        return [
            'sales' => self::salesQuery($filters)->count(),
            'responses' => (int) $row?->responses,
            'rated' => (int) $row?->rated,
            'average' => round((float) $row?->average, 1),
            'comments' => (int) $row?->comments,
            'stars' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($star) => [$star => (int) ($stars[$star] ?? 0)])->all(),
            'types' => collect(array_keys(feedbackTypes()))->mapWithKeys(fn ($type) => [$type => (int) ($types[$type] ?? 0)])->all(),
        ];
    }

    public function render()
    {
        $filters = $this->filters();
        $sortField = in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'sales.date';

        $rows = self::filteredQuery($filters)
            ->with(['account:id,name,mobile', 'branch:id,name', 'createdUser:id,name'])
            ->orderBy($sortField, $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('sales.id')
            ->paginate($this->perPage);

        $assignedBranchIds = Auth::user()?->branches->pluck('branch_id')->all() ?? [];

        return view('livewire.report.sale.feedback-report', [
            'rows' => $rows,
            'summary' => self::summary($filters),
            'ranges' => self::RANGES,
            'activeRange' => $this->currentRange(),
            'branches' => Branch::query()
                ->when($assignedBranchIds, fn ($q) => $q->whereIn('id', $assignedBranchIds))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }
}
