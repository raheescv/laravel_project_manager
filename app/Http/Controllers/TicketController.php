<?php

namespace App\Http\Controllers;

use App\Actions\Ticket\Attachment\DeleteAction as AttachmentDeleteAction;
use App\Actions\Ticket\BroadcastActivityAction;
use App\Actions\Ticket\CreateAction;
use App\Actions\Ticket\DeleteAction;
use App\Actions\Ticket\UpdateAction;
use App\Events\TicketActivity;
use App\Http\Requests\Ticket\TicketRequest;
use App\Http\Resources\Ticket\TicketCardResource;
use App\Http\Resources\Ticket\TicketResource;
use App\Models\Ticket;
use App\Notifications\TicketNotification;
use App\Services\TenantService;
use App\Traits\ApiResponseTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    use ApiResponseTrait;

    /** Cards sent per status column; the column header still shows the full count. */
    public const COLUMN_LIMIT = 100;

    /**
     * The board; `?ticket={id}` (the link in a ticket notification) opens that
     * ticket's detail and marks its notifications read.
     */
    public function index(Request $request): View
    {
        $ticketId = (int) $request->query('ticket');
        if ($ticketId > 0) {
            Auth::user()->unreadNotifications()
                ->where('type', TicketNotification::class)
                ->where('data->model_id', $ticketId)
                ->update(['read_at' => now()]);
        }

        return $this->console('board');
    }

    public function import(): View
    {
        return $this->console('import');
    }

    private function console(string $view): View
    {
        $user = Auth::user();

        return view('ticket.index', [
            'view' => $view,
            'liveChannel' => TicketActivity::channelName((int) app(TenantService::class)->getCurrentTenantId()),
            'permissions' => collect(['create', 'edit', 'delete', 'comment', 'import'])
                ->mapWithKeys(fn (string $action): array => [$action => $user->can("ticket.{$action}")])
                ->all(),
        ]);
    }

    public function board(Request $request): JsonResponse
    {
        $filter = [
            'search' => trim((string) $request->input('search')),
            'status' => (string) $request->input('status'),
            'group' => (string) $request->input('group'),
            'from_date' => (string) $request->input('from_date'),
            'to_date' => (string) $request->input('to_date'),
        ];

        $filtered = fn (array $except = []): Builder => Ticket::query()->filter(array_diff_key($filter, array_flip($except)));

        $counts = $filtered()->toBase()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $columns = [];
        foreach (array_keys(Ticket::statuses()) as $status) {
            $columns[$status] = [
                'total' => (int) ($counts[$status] ?? 0),
                'tickets' => (int) ($counts[$status] ?? 0) === 0 ? [] : TicketCardResource::collection(
                    $filtered()
                        ->select(['id', 'title', 'status', 'group', 'created_by', 'created_at'])
                        ->selectRaw('LEFT(description, ?) as excerpt', [TicketCardResource::EXCERPT_LENGTH + 20])
                        ->where('status', $status)
                        ->withCount(['attachments', 'comments'])
                        ->with([
                            'creator:id,name',
                            'attachments' => fn ($q) => $q->select(['id', 'ticket_id', 'file_path', 'mime_type'])->where('mime_type', 'like', 'image/%')->orderBy('id'),
                        ])
                        ->orderByDesc('id')
                        ->limit(self::COLUMN_LIMIT)
                        ->get()
                )->resolve(),
            ];
        }

        $groups = $filtered(['group'])->toBase()
            ->selectRaw('`group` as name, COUNT(*) as aggregate')
            ->groupBy('group')
            ->orderBy('group')
            ->get()
            ->map(fn ($row): array => ['name' => $row->name, 'count' => (int) $row->aggregate])
            ->values();

        return $this->sendSuccess([
            'columns' => $columns,
            'groups' => $groups,
            'all_groups' => Ticket::groupNames(),
        ], 'Tickets loaded.');
    }

    public function show(int $id): JsonResponse
    {
        $ticket = Ticket::with([
            'attachments',
            'comments' => fn ($q) => $q->with('creator:id,name')->latest(),
            'creator:id,name',
            'updater:id,name',
        ])->find($id);

        if (! $ticket) {
            return $this->sendNotFoundError('Ticket not found.');
        }

        return $this->sendSuccess(TicketResource::make($ticket)->resolve(), 'Ticket loaded.');
    }

    public function store(TicketRequest $request, CreateAction $action): JsonResponse
    {
        $response = $action->execute($request->safe()->except('files'), Auth::id(), $request->file('files', []));

        return $this->respond($response, 'created', null, 201);
    }

    public function update(int $id, TicketRequest $request, UpdateAction $action): JsonResponse
    {
        $response = $action->execute($request->safe()->except('files'), $id, Auth::id(), $request->file('files', []));

        return $this->respond($response, 'updated', $id);
    }

    public function status(int $id, Request $request, UpdateAction $action): JsonResponse
    {
        $request->validate(['status' => ['required', Rule::in(array_keys(Ticket::statuses()))]]);

        $response = $action->execute(['status' => $request->input('status')], $id, Auth::id());
        if ($response['success']) {
            $response['message'] = 'Moved to '.Ticket::statuses()[$request->input('status')].'.';
        }

        return $this->respond($response, 'status', $id);
    }

    public function destroy(int $id, DeleteAction $action): JsonResponse
    {
        return $this->respond($action->execute($id), 'deleted', $id);
    }

    public function destroyAttachment(int $id, int $attachmentId, AttachmentDeleteAction $action): JsonResponse
    {
        return $this->respond($action->execute($id, $attachmentId), 'attachment', $id);
    }

    /**
     * Turn an action result into a JSON response, re-reading the full ticket on
     * success and telling the tenant's other open consoles to refresh.
     *
     * @param  array{success: bool, message: string, data?: mixed}  $response
     */
    private function respond(array $response, string $activity, ?int $ticketId = null, int $code = 200): JsonResponse
    {
        if (! $response['success']) {
            return $this->sendError($response['message'], [], 422);
        }

        $ticketId ??= $response['data'] instanceof Ticket ? $response['data']->id : null;
        app(BroadcastActivityAction::class)->execute($activity, $ticketId);

        $data = $response['data'] instanceof Ticket
            ? TicketResource::make($response['data']->load(['attachments', 'comments' => fn ($q) => $q->with('creator:id,name')->latest(), 'creator:id,name', 'updater:id,name']))->resolve()
            : $response['data'];

        return $this->sendSuccess($data, $response['message'], $code);
    }
}
