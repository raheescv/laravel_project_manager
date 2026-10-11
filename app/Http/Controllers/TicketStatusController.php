<?php

namespace App\Http\Controllers;

use App\Actions\Ticket\BroadcastActivityAction;
use App\Actions\Ticket\Status\CreateAction;
use App\Actions\Ticket\Status\DeleteAction;
use App\Actions\Ticket\Status\ReorderAction;
use App\Actions\Ticket\Status\UpdateAction;
use App\Http\Requests\Ticket\TicketStatusRequest;
use App\Http\Resources\Ticket\TicketStatusResource;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The board's columns: add, rename, recolour, reorder and remove them.
 */
class TicketStatusController extends Controller
{
    use ApiResponseTrait;

    /** Columns with how many tickets each holds (unfiltered), for the manager. */
    public function index(): JsonResponse
    {
        return $this->sendSuccess($this->columns(), 'Columns loaded.');
    }

    public function store(TicketStatusRequest $request, CreateAction $action): JsonResponse
    {
        return $this->respond($action->execute($request->validated()), 201);
    }

    public function update(int $statusId, TicketStatusRequest $request, UpdateAction $action): JsonResponse
    {
        return $this->respond($action->execute($statusId, $request->validated()));
    }

    public function reorder(Request $request, ReorderAction $action): JsonResponse
    {
        $request->validate(['ids' => ['required', 'array', 'max:'.TicketStatus::MAX_COLUMNS], 'ids.*' => ['integer']]);

        return $this->respond($action->execute($request->input('ids')));
    }

    public function destroy(int $statusId, Request $request, DeleteAction $action): JsonResponse
    {
        $request->validate(['move_to' => ['nullable', 'string', 'max:50']]);

        return $this->respond($action->execute($statusId, $request->input('move_to')));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function columns(): array
    {
        $counts = Ticket::query()->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return TicketStatus::ordered()
            ->map(fn (TicketStatus $status): array => TicketStatusResource::make($status)->resolve() + ['tickets_count' => (int) ($counts[$status->key] ?? 0)])
            ->values()
            ->all();
    }

    /**
     * Reply with the fresh column list and tell the tenant's other consoles to redraw.
     *
     * @param  array{success: bool, message: string}  $response
     */
    private function respond(array $response, int $code = 200): JsonResponse
    {
        if (! $response['success']) {
            return $this->sendError($response['message'], [], 422);
        }

        app(BroadcastActivityAction::class)->execute('columns');

        return $this->sendSuccess($this->columns(), $response['message'], $code);
    }
}
