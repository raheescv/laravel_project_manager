<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Sale\CreateAction;
use App\Actions\V1\Sale\DeleteAction;
use App\Actions\V1\Sale\GetAction;
use App\Actions\V1\Sale\ListAction;
use App\Actions\V1\Sale\UpdateAction;
use App\Helpers\Facades\SaleHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Sale\IndexRequest;
use App\Http\Requests\V1\Sale\StoreRequest;
use App\Http\Requests\V1\Sale\UpdateRequest;
use App\Http\Resources\V1\Sale\SaleResource;
use App\Traits\ApiResponseTrait;
use App\Traits\UsesBrowsershot;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

#[Group('Mobile - Sales')]
class SaleController extends Controller
{
    use ApiResponseTrait;
    use UsesBrowsershot;

    /**
     * List sales.
     *
     * Returns a paginated list of sales for the authenticated user's assigned branches,
     * with optional filters (search, status, date range, customer, payment method).
     */
    public function index(ListAction $action, IndexRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Sales retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sales: '.$e->getMessage());
        }
    }

    /**
     * View sale.
     *
     * Retrieves a sale with line items and payments, formatted for printing.
     */
    public function show(GetAction $action, int $sale): JsonResponse
    {
        try {
            $result = $action->execute($sale);

            return $this->sendSuccess(new SaleResource($result), 'Sale retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Sale not found.');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale: '.$e->getMessage());
        }
    }

    /**
     * Print sale receipt.
     *
     * Renders the thermal (80mm) invoice through Chromium (Browsershot) and returns
     * it as a PDF, so Arabic shapes correctly — unlike the app's on-device PDF engine.
     */
    public function receipt(int $sale): Response
    {
        try {
            // SaleHelper renders the shared sale.print thermal blade to HTML; it
            // returns a redirect (non-string) when the sale can't be found.
            $html = SaleHelper::saleInvoice($sale);
            if (! is_string($html)) {
                return $this->sendNotFoundError('Sale not found.');
            }

            // preferCSSPageSize lets Chromium honour the blade's `@page { size: 80mm auto }`,
            // producing a single continuous 80mm-wide receipt instead of an A4 page.
            $pdf = $this->makeBrowsershot($html)
                ->showBackground()
                ->pdf([
                    'printBackground' => true,
                    'preferCSSPageSize' => true,
                    'scale' => 1,
                ]);

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="invoice-'.$sale.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            // Log the real Browsershot/Chromium failure so it can be diagnosed from
            // the server log even when APP_DEBUG is off and the app only shows a
            // short message.
            Log::error('Sale receipt PDF generation failed', [
                'sale_id' => $sale,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendServerError('Failed to generate receipt: '.$e->getMessage());
        }
    }

    /**
     * Create sale.
     *
     * Creates a completed sale from the final sale data sent by the app and records the payment.
     */
    public function store(CreateAction $action, StoreRequest $request): JsonResponse
    {
        try {
            $sale = $action->execute($request);

            return $this->sendSuccess(new SaleResource($sale), 'Sale saved successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to save sale: '.$e->getMessage(), [], 422);
        }
    }

    /**
     * Update sale.
     *
     * Reconciles an existing sale against the data sent by the app — updating,
     * adding and removing lines and payments — and (for a completed sale) re-runs
     * the stock and journal postings.
     */
    public function update(UpdateAction $action, UpdateRequest $request, int $sale): JsonResponse
    {
        try {
            $updated = $action->execute($request, $sale);

            return $this->sendSuccess(new SaleResource($updated), 'Sale updated successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Sale not found.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update sale: '.$e->getMessage(), [], 422);
        }
    }

    /**
     * Delete sale.
     *
     * Soft-deletes a sale with its line items and payments. Completed sales are
     * refused (they must be returned or cancelled instead, to keep the stock and
     * accounting intact). Gated by the `sale.delete` permission on the route.
     */
    public function destroy(DeleteAction $action, int $sale): JsonResponse
    {
        try {
            $action->execute($sale, auth()->id());

            return $this->sendSuccess([], 'Sale deleted successfully');
        } catch (\DomainException $e) {
            // Business-rule refusal (e.g. completed sale) — surface verbatim.
            return $this->sendError($e->getMessage(), [], 422);
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Sale not found.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete sale: '.$e->getMessage(), [], 422);
        }
    }
}
