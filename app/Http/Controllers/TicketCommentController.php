<?php

namespace App\Http\Controllers;

use App\Actions\Ticket\BroadcastActivityAction;
use App\Actions\Ticket\Comment\CreateAction;
use App\Actions\Ticket\Comment\DeleteAction;
use App\Actions\Ticket\Comment\UpdateAction;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketCommentController extends Controller
{
    use ApiResponseTrait;

    public function store(int $id, Request $request, CreateAction $action): JsonResponse
    {
        return $this->respond($action->execute($request->only('comment'), $id, Auth::id()), $id);
    }

    public function update(int $id, int $commentId, Request $request, UpdateAction $action): JsonResponse
    {
        return $this->respond($action->execute($request->only('comment'), $id, $commentId, Auth::id()), $id);
    }

    public function destroy(int $id, int $commentId, DeleteAction $action): JsonResponse
    {
        return $this->respond($action->execute($id, $commentId), $id);
    }

    /**
     * @param  array{success: bool, message: string, data?: mixed}  $response
     */
    private function respond(array $response, int $ticketId): JsonResponse
    {
        if (! $response['success']) {
            return $this->sendError($response['message'], [], 422);
        }

        app(BroadcastActivityAction::class)->execute('comment', $ticketId);

        return $this->sendSuccess(null, $response['message']);
    }
}
