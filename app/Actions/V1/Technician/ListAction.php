<?php

namespace App\Actions\V1\Technician;

use App\Actions\V1\Technician\Concerns\InteractsWithComplaint;
use App\Http\Requests\V1\Technician\IndexRequest;
use App\Http\Resources\V1\Technician\ComplaintListResource;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Builder;

/**
 * Paginated list of complaints assigned to the authenticated technician, with
 * status / search / date-range filters and infinite-scroll pagination.
 * Mirrors the row mapping of App\Livewire\Maintenance\Technician.
 */
class ListAction
{
    use InteractsWithComplaint;

    /**
     * @return array<string, mixed>
     */
    public function execute(IndexRequest $request): array
    {
        $filters = $request->validatedWithDefaults();

        $query = $this->ownedComplaints()
            ->with([
                'maintenance.property.building.group',
                'complaint.category',
            ]);

        $this->applyFilters($query, $filters);

        // Order by the parent maintenance appointment date via a correlated
        // sub-select so we keep the eager-loaded relations intact.
        $direction = $filters['sort_direction'];
        $query->orderBy(Maintenance::select('date')->whereColumn('maintenances.id', 'maintenance_complaints.maintenance_id'), $direction)->orderBy('maintenance_complaints.id', 'desc');

        $paginator = $query->paginate($filters['per_page']);

        return [
            'data' => ComplaintListResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? '', fn (Builder $q, $value) => $q->where('maintenance_complaints.status', $value))
            ->when($filters['search'] ?? '', function (Builder $q, $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('maintenance_complaints.technician_remark', 'like', "%{$search}%")
                        ->orWhereHas('complaint', fn (Builder $c) => $c->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('maintenance', function (Builder $m) use ($search) {
                            $m->whereHas('property', fn (Builder $p) => $p->where('number', 'like', "%{$search}%"))
                                ->orWhereHas('customer', fn (Builder $a) => $a->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('property.building', fn (Builder $b) => $b->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($filters['from_date'] ?? null, fn (Builder $q, $value) => $q->whereHas('maintenance', fn (Builder $m) => $m->whereDate('date', '>=', $value)))
            ->when($filters['to_date'] ?? null, fn (Builder $q, $value) => $q->whereHas('maintenance', fn (Builder $m) => $m->whereDate('date', '<=', $value)));
    }
}
