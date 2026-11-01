<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Product\AddImageAction;
use App\Actions\Product\DeleteAction;
use App\Actions\Product\DeleteImageAction;
use App\Actions\V1\Catalog\ListAction;
use App\Actions\V1\Catalog\OptionsAction;
use App\Actions\V1\Catalog\SaveAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Catalog\ImageRequest;
use App\Http\Requests\V1\Catalog\ListRequest;
use App\Http\Requests\V1\Catalog\SaveRequest;
use App\Http\Resources\V1\Catalog\CatalogItemDetailResource;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Settings → Catalog: managing products and services from the app. `{type}` is
 * fixed per route group (product | service) and each group is gated on that
 * type's web permissions.
 */
#[Group('Mobile - Catalog')]
class CatalogItemController extends Controller
{
    use ApiResponseTrait;

    /**
     * List products or services.
     *
     * Search, department / main category / brand / status filters, sort by any
     * table column (`sort` + `direction`; relation columns sort by name),
     * paginated. `summary` carries whole-catalog KPI counts.
     */
    public function index(ListRequest $request, ListAction $action): JsonResponse
    {
        $type = $this->type();
        try {
            return $this->sendSuccess($action->execute($type, $request), 'Items retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve items: '.$e->getMessage());
        }
    }

    /**
     * Picker options.
     *
     * Departments, main and sub categories, units and brands for the filters
     * and the Add / Edit form, plus the barcode settings the form adapts to.
     */
    public function options(OptionsAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute(), 'Options retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve options: '.$e->getMessage());
        }
    }

    /**
     * View an item.
     *
     * Everything the Add / Edit form shows, including picker ids and photos.
     */
    public function show(int $id): JsonResponse
    {
        $type = $this->type();
        try {
            return $this->sendSuccess($this->detail($this->find($id)), 'Item retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError(ucfirst($type).' not found.');
        }
    }

    /**
     * Create an item.
     *
     * Delegates to the web CreateAction: code and barcode are generated when
     * left empty, and `add <name>` in a picker field quick-creates it.
     */
    public function store(SaveRequest $request, SaveAction $action): JsonResponse
    {
        $type = $this->type();
        try {
            $item = $action->execute($type, $request->validated(), $request->user());

            return $this->sendSuccess($this->detail($item), ucfirst($type).' created successfully', 201);
        } catch (RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Update an item.
     */
    public function update(int $id, SaveRequest $request, SaveAction $action): JsonResponse
    {
        $type = $this->type();
        try {
            $item = $action->execute($type, $request->validated(), $request->user(), $this->find($id));

            return $this->sendSuccess($this->detail($item), ucfirst($type).' updated successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError(ucfirst($type).' not found.');
        } catch (RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Delete an item.
     *
     * Refused once the item appears on a sale, return or purchase.
     */
    public function destroy(int $id, DeleteAction $action): JsonResponse
    {
        $type = $this->type();
        try {
            $this->find($id);
            // DeleteAction deletes first and checks usage after, so a refusal
            // must roll the delete back.
            DB::transaction(function () use ($action, $id): void {
                $response = $action->execute($id);
                if (! $response['success']) {
                    throw new RuntimeException($response['message']);
                }
            });

            return $this->sendSuccess(null, ucfirst($type).' deleted successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError(ucfirst($type).' not found.');
        } catch (RuntimeException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }
    }

    /**
     * Add a photo.
     *
     * One image per request (multipart field `image`). The first photo becomes
     * the thumbnail the POS shows.
     */
    public function storeImage(int $id, ImageRequest $request, AddImageAction $action): JsonResponse
    {
        $type = $this->type();
        try {
            $item = $this->find($id);
            $image = $action->execute($item, $request->file('image'));
            if (! $item->thumbnail) {
                $item->update(['thumbnail' => $image->path]);
            }

            return $this->sendSuccess($this->detail($item), 'Photo added', 201);
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError(ucfirst($type).' not found.');
        }
    }

    /**
     * Remove a photo.
     */
    public function destroyImage(int $id, int $imageId, DeleteImageAction $action): JsonResponse
    {
        try {
            $item = $this->find($id);
            $item->images()->findOrFail($imageId);
            $response = $action->execute($imageId);
            if (! $response['success']) {
                return $this->sendError($response['message'], [], 422);
            }

            return $this->sendSuccess($this->detail($item), 'Photo removed');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Photo not found.');
        }
    }

    /**
     * The route group's type. Read by name: route defaults reach controller
     * arguments by position, after the URL parameters.
     */
    private function type(): string
    {
        return (string) request()->route('type');
    }

    /** An item of this route group's type — never a service through the product routes. */
    private function find(int $id): Product
    {
        return Product::query()->where('type', $this->type())->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Product $item): array
    {
        $item->refresh()->load(['department:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'unit:id,name', 'brand:id,name', 'images']);

        return CatalogItemDetailResource::make($item)->resolve(request());
    }
}
