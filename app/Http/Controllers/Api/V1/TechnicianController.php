<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Technician\AddAttachmentsAction;
use App\Actions\V1\Technician\AddNoteAction;
use App\Actions\V1\Technician\AddSupplyItemAction;
use App\Actions\V1\Technician\CompleteAction;
use App\Actions\V1\Technician\DashboardAction;
use App\Actions\V1\Technician\DeleteAttachmentAction;
use App\Actions\V1\Technician\DeleteNoteAction;
use App\Actions\V1\Technician\DeleteSupplyItemAction;
use App\Actions\V1\Technician\GetAction;
use App\Actions\V1\Technician\ListAction;
use App\Actions\V1\Technician\SaveRemarkAction;
use App\Actions\V1\Technician\UpdateSupplyItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Technician\IndexRequest;
use App\Http\Requests\V1\Technician\StoreSupplyItemRequest;
use App\Http\Requests\V1\Technician\UpdateSupplyItemRequest;
use App\Http\Resources\V1\Technician\ComplaintDetailResource;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Technician maintenance workflow — mirrors App\Livewire\Maintenance\Complaint.
 * Every route is scoped to complaints assigned to the authenticated technician
 * (technician_id = auth id).
 */
#[Group('Mobile - Technician')]
class TechnicianController extends Controller
{
    use ApiResponseTrait;

    /**
     * Technician dashboard.
     *
     * Workload summary: KPI counts, priority breakdown of open jobs, recent complaints.
     */
    public function dashboard(DashboardAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute(), 'Dashboard retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve dashboard: '.$e->getMessage());
        }
    }

    /**
     * List assigned complaints.
     *
     * Paginated, with status / search / date-range filters for infinite scroll.
     */
    public function index(ListAction $action, IndexRequest $request): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($request), 'Complaints retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve complaints: '.$e->getMessage());
        }
    }

    /**
     * View a complaint.
     *
     * Full detail payload for the workflow screen.
     */
    public function show(GetAction $action, int $complaint): JsonResponse
    {
        return $this->respondDetail(fn () => $action->execute($complaint), 'Complaint retrieved successfully');
    }

    /**
     * Save technician remark.
     *
     * Persists the remark on a pending complaint (Complaint::save('pending')).
     */
    public function update(SaveRemarkAction $action, Request $request, int $complaint): JsonResponse
    {
        $data = $request->validate([
            'technician_remark' => ['nullable', 'string', 'max:5000'],
        ]);

        return $this->respondDetail(
            fn () => $action->execute($complaint, $data['technician_remark'] ?? null),
            'Saved successfully'
        );
    }

    /**
     * Complete a complaint.
     *
     * Requires a non-empty remark; auto-completes the parent maintenance.
     */
    public function complete(CompleteAction $action, Request $request, int $complaint): JsonResponse
    {
        $data = $request->validate([
            'technician_remark' => ['nullable', 'string', 'max:5000'],
        ]);

        return $this->respondDetail(
            fn () => $action->execute($complaint, $data['technician_remark'] ?? null),
            'Complaint completed successfully'
        );
    }

    /**
     * Add a supply item.
     *
     * Lazily creates the supply request; supports barcode lookup (addCart).
     */
    public function storeSupplyItem(AddSupplyItemAction $action, StoreSupplyItemRequest $request, int $complaint): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($complaint, $request->validated()),
            'Successfully added to cart'
        );
    }

    /**
     * Update a supply item.
     */
    public function updateSupplyItem(UpdateSupplyItemAction $action, UpdateSupplyItemRequest $request, int $item): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($item, $request->validated()),
            'Item updated successfully'
        );
    }

    /**
     * Delete a supply item.
     */
    public function deleteSupplyItem(DeleteSupplyItemAction $action, int $item): JsonResponse
    {
        return $this->respondDetail(fn () => $action->execute($item), 'Successfully deleted item');
    }

    /**
     * Add a note.
     */
    public function storeNote(AddNoteAction $action, Request $request, int $complaint): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        return $this->respondDetail(fn () => $action->execute($complaint, $data['note']), 'Note added successfully');
    }

    /**
     * Delete a note.
     */
    public function deleteNote(DeleteNoteAction $action, int $note): JsonResponse
    {
        return $this->respondDetail(fn () => $action->execute($note), 'Note deleted successfully');
    }

    /**
     * Upload attachments.
     *
     * Multipart, one or more files (image / video / pdf / doc).
     */
    public function storeAttachments(AddAttachmentsAction $action, Request $request, int $complaint): JsonResponse
    {
        $request->validate([
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,gif,webp,heic,mp4,mov,avi,webm,pdf,doc,docx'],
        ]);

        return $this->respondDetail(
            fn () => $action->execute($complaint, $request->file('attachments')),
            'Files uploaded successfully'
        );
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(DeleteAttachmentAction $action, int $attachment): JsonResponse
    {
        return $this->respondDetail(fn () => $action->execute($attachment), 'Successfully deleted attachment');
    }

    /**
     * Run an action returning a MaintenanceComplaint and wrap it in the detail
     * resource with consistent error handling (the web loadData() reconciliation
     * is reproduced by every write re-fetching the detail).
     */
    private function respondDetail(callable $resolver, string $message): JsonResponse
    {
        try {
            return $this->sendSuccess(new ComplaintDetailResource($resolver()), $message);
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Complaint not found.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $this->sendError($e->getMessage(), [], $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->sendServerError('Request failed: '.$e->getMessage());
        }
    }
}
