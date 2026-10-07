<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Technician\Checklist\AddFixtureAction;
use App\Actions\V1\Technician\Checklist\DeleteFixtureAction;
use App\Actions\V1\Technician\Checklist\GetAction;
use App\Actions\V1\Technician\Checklist\ListAction;
use App\Actions\V1\Technician\Checklist\MarkOkAction;
use App\Actions\V1\Technician\Checklist\PdfAction;
use App\Actions\V1\Technician\Checklist\SaveFixturePhotoAction;
use App\Actions\V1\Technician\Checklist\SaveLinePhotoAction;
use App\Actions\V1\Technician\Checklist\SealAction;
use App\Actions\V1\Technician\Checklist\SignAction;
use App\Actions\V1\Technician\Checklist\SignFixtureAction;
use App\Actions\V1\Technician\Checklist\UpdateFixtureAction;
use App\Actions\V1\Technician\Checklist\UpdateLineAction;
use App\Enums\RentOut\ChecklistPhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Technician\Checklist\FixturePhotoRequest;
use App\Http\Requests\V1\Technician\Checklist\IndexRequest;
use App\Http\Requests\V1\Technician\Checklist\MarkOkRequest;
use App\Http\Requests\V1\Technician\Checklist\PhaseRequest;
use App\Http\Requests\V1\Technician\Checklist\PhotoRequest;
use App\Http\Requests\V1\Technician\Checklist\SealRequest;
use App\Http\Requests\V1\Technician\Checklist\ShowRequest;
use App\Http\Requests\V1\Technician\Checklist\SignFixtureRequest;
use App\Http\Requests\V1\Technician\Checklist\SignRequest;
use App\Http\Requests\V1\Technician\Checklist\StoreFixtureRequest;
use App\Http\Requests\V1\Technician\Checklist\UpdateFixtureRequest;
use App\Http\Requests\V1\Technician\Checklist\UpdateLineRequest;
use App\Http\Resources\V1\Technician\ChecklistDetailResource;
use App\Http\Resources\V1\Technician\ChecklistJobResource;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The RentOut move-in / move-out hand-over checklist, worked from the technician
 * app — mirrors App\Livewire\RentOut\Tabs\ChecklistTab. Every route is scoped to
 * rent-outs the user coordinates (facility or leasing coordinator).
 */
#[Group('Mobile - Technician Checklist')]
class TechnicianChecklistController extends Controller
{
    use ApiResponseTrait;

    /**
     * List hand-overs.
     *
     * One row per rent-out × phase the user coordinates. `status` open (default) / completed / all;
     * `date_basis` (move_in / move_out) with `from_date` / `to_date` filters on the hand-over date.
     */
    public function index(ListAction $action, IndexRequest $request): JsonResponse
    {
        try {
            $jobs = $action->execute($request->filters())
                ->map(fn (array $job) => (new ChecklistJobResource($job['rentOut'], $job['phase']))->resolve($request));

            return $this->sendSuccess($jobs, 'Checklists retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve checklists: '.$e->getMessage());
        }
    }

    /**
     * View a hand-over checklist.
     *
     * Items, fixture comments and signatures for one phase (defaults to the open phase).
     */
    public function show(GetAction $action, ShowRequest $request, int $rentOut): JsonResponse
    {
        return $this->respondDetail(fn () => $action->execute($rentOut), $request->phase(), 'Checklist retrieved successfully');
    }

    /**
     * Download the checklist PDF.
     *
     * The Unit Handover & Snagging form (items, fixtures, signatures) as `application/pdf`.
     */
    public function pdf(PdfAction $action, int $rentOut): Response|JsonResponse
    {
        try {
            $result = $action->execute($rentOut);

            return response($result['pdf'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$result['filename'].'"',
            ]);
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Checklist not found.');
        } catch (\Throwable $e) {
            // Log the real Browsershot/Chromium failure so it can be diagnosed from the server.
            Log::error('Technician checklist PDF failed', ['rent_out_id' => $rentOut, 'error' => $e->getMessage()]);

            return $this->sendServerError('Could not generate the checklist PDF.');
        }
    }

    /**
     * Record a checklist item.
     *
     * Status (move-in: ok/null · move-out: ok/not_ok/null), comment, qty and damage cost — only the keys sent change.
     */
    public function updateLine(UpdateLineAction $action, UpdateLineRequest $request, int $rentOut, int $line): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($rentOut, $line, $request->phase(), $request->lineChanges()),
            $request->phase(),
            'Checklist item saved'
        );
    }

    /**
     * Photograph a checklist item.
     *
     * Multipart `photo`; replaces this phase's photo of the item.
     */
    public function linePhoto(SaveLinePhotoAction $action, PhotoRequest $request, int $rentOut, int $line): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($rentOut, $line, $request->phase(), $request->file('photo')),
            $request->phase(),
            'Photo saved'
        );
    }

    /**
     * Mark items OK.
     *
     * Sets present / good on the given items that have no status yet for the phase.
     */
    public function markOk(MarkOkAction $action, MarkOkRequest $request, int $rentOut): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($rentOut, $request->phase(), $request->validated('line_ids')),
            $request->phase(),
            'Items marked'
        );
    }

    /**
     * Sign the hand-over.
     *
     * The user's own coordinator role, or the lessee in person. PNG data URI.
     */
    public function sign(SignAction $action, SignRequest $request, int $rentOut): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($rentOut, $request->phase(), $request->role(), $request->validated('signature'), $request->validated('signer_name')),
            $request->phase(),
            'Signature saved'
        );
    }

    /**
     * Seal the hand-over.
     *
     * Requires all three signatures; records the actual hand-over date and remarks.
     */
    public function seal(SealAction $action, SealRequest $request, int $rentOut): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($rentOut, $request->phase(), collect($request->validated())->only(['actual_date', 'remarks'])->all()),
            $request->phase(),
            'Hand-over sealed'
        );
    }

    /**
     * Add a fixture comment.
     *
     * Opens the area's Fixture Comments block when it has none yet.
     */
    public function storeFixture(AddFixtureAction $action, StoreFixtureRequest $request, int $rentOut): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($rentOut, $request->validated('category'), collect($request->validated())->only(['comments', 'status'])->all()),
            $request->phase(),
            'Fixture comment added'
        );
    }

    /**
     * Edit a fixture comment.
     */
    public function updateFixture(UpdateFixtureAction $action, UpdateFixtureRequest $request, int $entry): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($entry, $request->fixtureChanges()),
            $request->phase(),
            'Fixture comment saved'
        );
    }

    /**
     * Photograph a fixture.
     *
     * Multipart `photo` + `which` (before / after).
     */
    public function fixturePhoto(SaveFixturePhotoAction $action, FixturePhotoRequest $request, int $entry): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($entry, $request->validated('which'), $request->file('photo')),
            $request->phase(),
            'Photo saved'
        );
    }

    /**
     * Delete a fixture comment.
     */
    public function deleteFixture(DeleteFixtureAction $action, PhaseRequest $request, int $entry): JsonResponse
    {
        return $this->respondDetail(fn () => $action->execute($entry), $request->phase(), 'Fixture comment removed');
    }

    /**
     * Owner accepts an area.
     *
     * Only once every fixture in the area is completed. PNG data URI.
     */
    public function signFixture(SignFixtureAction $action, SignFixtureRequest $request, int $rentOut, int $area): JsonResponse
    {
        return $this->respondDetail(
            fn () => $action->execute($rentOut, $area, $request->validated('owner_name'), $request->validated('signature')),
            $request->phase(),
            'Owner signature saved'
        );
    }

    /**
     * Run an action returning the rent-out and answer with its checklist for
     * [phase], with the same error mapping as the complaint workflow. A shared
     * action's refusal (RuntimeException) is the user's to fix, so it is a 422.
     */
    private function respondDetail(callable $resolver, ?ChecklistPhase $phase, string $message): JsonResponse
    {
        try {
            return $this->sendSuccess(new ChecklistDetailResource($resolver(), $phase), $message);
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Checklist not found.');
        } catch (HttpException $e) {
            return $this->sendError($e->getMessage(), [], $e->getStatusCode());
        } catch (\RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Exception $e) {
            return $this->sendServerError('Request failed: '.$e->getMessage());
        }
    }
}
