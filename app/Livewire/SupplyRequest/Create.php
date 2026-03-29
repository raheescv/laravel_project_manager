<?php

namespace App\Livewire\SupplyRequest;

use App\Actions\SupplyRequest\CreateUpdateAction;
use App\Actions\SupplyRequest\StatusChangeAction;
use App\Enums\SupplyRequest\SupplyRequestStatus;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Property;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestImage;
use App\Models\SupplyRequestItem;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public $supply_request = [];

    public $table_id;

    public $items = [];

    public $item = [];

    public $images = [];

    public $imageList = [];

    public $note = '';

    public $notes = [];

    public $status = '';

    public $branches = [];

    public $type = 'Add';

    public $preFilledDropDowns = [];

    public $payment_mode_id = '';

    public $paymentMethods = [];

    protected $rules = [
        'supply_request.property_id' => 'required',
        'supply_request.date' => 'required',
    ];

    protected $messages = [
        'supply_request.property_id' => 'The property no field is required',
        'supply_request.date' => 'The date field is required',
    ];

    public function mount($id = null, $type = 'Add')
    {
        $this->table_id = $id;
        $this->type = $type;
        $this->branches = Branch::pluck('name', 'id');
        $this->paymentMethods = Account::whereIn('id', cache('payment_methods', []))->pluck('name', 'id');
        $this->preFilledDropDowns = ['group' => [], 'building' => [], 'type' => [], 'property' => []];
        $this->initItem();
        $this->items = [];
        $this->images = [];
        $this->imageList = [];
        $this->notes = [];
        $this->status = '';

        if ($this->table_id) {
            $this->loadData();
        } else {
            $this->supply_request = [
                'type' => $this->type,
                'property_id' => '',
                'property_group_id' => '',
                'property_building_id' => '',
                'property_type_id' => '',
                'date' => date('Y-m-d'),
                'order_no' => time(),
                'contact_person' => '',
                'total' => 0,
                'other_charges' => 0,
                'grand_total' => 0,
                'status' => SupplyRequestStatus::REQUIREMENT->value,
                'remarks' => '',
                'creator' => Auth::user()->name,
                'approver' => '',
                'accountant' => '',
                'final_approver' => '',
                'completer' => '',
            ];
        }
        $this->status = $this->supply_request['status'];
    }

    public function loadData(): void
    {
        $model = SupplyRequest::with('property.building.group', 'property.type')->find($this->table_id);
        $this->supply_request = $model->toArray();
        $this->supply_request['creator'] = $model->creator?->name ?? '';
        $this->supply_request['created_at_formatted'] = $model->created_at?->format('d M Y, h:i A') ?? '';
        $this->supply_request['approver'] = $model->approver?->name ?? '';
        $this->supply_request['approved_at_formatted'] = $model->approved_at?->format('d M Y, h:i A') ?? '';
        $this->supply_request['accountant'] = $model->accountant?->name ?? '';
        $this->supply_request['accounted_at_formatted'] = $model->accounted_at?->format('d M Y, h:i A') ?? '';
        $this->supply_request['final_approver'] = $model->finalApprover?->name ?? '';
        $this->supply_request['final_approved_at_formatted'] = $model->final_approved_at?->format('d M Y, h:i A') ?? '';
        $this->supply_request['completer'] = $model->completer?->name ?? '';
        $this->supply_request['completed_at_formatted'] = $model->completed_at?->format('d M Y, h:i A') ?? '';
        $this->supply_request['payment_mode_name'] = $model->paymentMode?->name ?? '';
        $this->payment_mode_id = $model->payment_mode_id ?? '';

        // Pre-fill property dropdowns for edit
        if ($model->property) {
            $property = $model->property;
            $building = $property->building;
            $group = $building?->group;
            $type = $property->type;

            $this->supply_request['property_group_id'] = $group?->id ?? '';
            $this->supply_request['property_building_id'] = $building?->id ?? '';
            $this->supply_request['property_type_id'] = $type?->id ?? '';

            $this->preFilledDropDowns = [
                'group' => $group ? [$group->id => $group->name] : [],
                'building' => $building ? [$building->id => $building->name] : [],
                'type' => $type ? [$type->id => $type->name] : [],
                'property' => [$property->id => $property->number.($building ? ' - '.$building->name : '')],
            ];
        }

        foreach ($model->items as $value) {
            $single = $value->toArray();
            $single['branch_name'] = $value->branch?->name ?? 'Main Store';
            $single['product_name'] = $value->product?->name ?? '';
            $single['edit_flag'] = false;
            $this->items[] = $single;
        }

        foreach ($model->images as $value) {
            // Check if the file exists on disk
            $relativePath = str_replace('/storage/', '', $value->path);
            $fileExists = Storage::disk('public')->exists($relativePath);

            $this->imageList[] = [
                'id' => $value->id,
                'path' => asset($value->path),
                'name' => $value->name,
                'type' => $value->type,
                'is_video' => $value->is_video,
                'is_pdf' => $value->is_pdf,
                'is_image' => str_contains($value->type ?? '', 'image'),
                'file_exists' => $fileExists,
            ];
        }

        foreach ($model->notes()->with('creator')->get() as $value) {
            $this->notes[] = [
                'id' => $value->id,
                'note' => $value->note,
                'creator' => $value->creator?->name ?? '',
                'created_at' => $value->created_at,
                'delete_flag' => false,
            ];
        }

    }

    public function initItem(): void
    {
        $defaultBranch = Branch::first();
        $this->item = [
            'branch_id' => $defaultBranch?->id,
            'barcode' => '',
            'product_id' => null,
            'mode' => 'New',
            'quantity' => 1,
            'unit_price' => 0,
            'total' => 0,
            'remarks' => '',
        ];
    }

    public function updated($key, $value): void
    {
        if ($key === 'item.product_id' && $value) {
            $product = Product::find($value);
            if ($product) {
                $this->item['unit_price'] = $product->cost ?? 0;
                $this->item['total'] = $this->item['unit_price'] * $this->item['quantity'];
            }
        }

        if ($key === 'supply_request.other_charges') {
            $this->supply_request['grand_total'] = floatval($value) + floatval($this->supply_request['total']);
        }

        if (in_array($key, ['item.quantity', 'item.unit_price'])) {
            $this->item['total'] = floatval($this->item['quantity']) * floatval($this->item['unit_price']);
        }

        if ($key === 'item.barcode' && $value) {
            $product = Product::where('barcode', $value)->first();
            if ($product) {
                $this->item['product_id'] = $product->id;
                $this->item['unit_price'] = $product->cost ?? 0;
                $this->item['total'] = $this->item['unit_price'] * $this->item['quantity'];
                $this->addCart();
            }
        }
    }

    public function getPropertyDetails(int $propertyId): array
    {
        $property = Property::with(['group', 'building', 'type'])->find($propertyId);

        if (! $property) {
            return [];
        }

        $this->supply_request['property_id'] = $property->id;
        $this->supply_request['property_group_id'] = $property->property_group_id;
        $this->supply_request['property_building_id'] = $property->property_building_id;
        $this->supply_request['property_type_id'] = $property->property_type_id;

        return [
            'property_group_id' => $property->property_group_id,
            'property_group_name' => $property->group?->name ?? '',
            'property_building_id' => $property->property_building_id,
            'property_building_name' => $property->building?->name ?? '',
            'property_type_id' => $property->property_type_id,
            'property_type_name' => $property->type?->name ?? '',
        ];
    }

    public function editCartItem($key): void
    {
        $this->items[$key]['edit_flag'] = ! $this->items[$key]['edit_flag'];
        $this->mainCalculator();
    }

    public function cartCalculator($key): void
    {
        $this->items[$key]['total'] = floatval($this->items[$key]['quantity']) * floatval($this->items[$key]['unit_price']);
        $this->mainCalculator();
    }

    public function mainCalculator(): void
    {
        $list = collect($this->items);
        $this->supply_request['total'] = $list->sum('total');
        $this->supply_request['grand_total'] = floatval($this->supply_request['other_charges'] ?? 0) + $this->supply_request['total'];
    }

    public function deleteItem($key): void
    {
        try {
            DB::beginTransaction();
            if (isset($this->items[$key]['id'])) {
                SupplyRequestItem::find($this->items[$key]['id'])?->delete();
            }
            unset($this->items[$key]);
            $this->items = array_values($this->items);
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'Successfully deleted item']);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function deleteImage($key): void
    {
        try {
            DB::beginTransaction();
            if (isset($this->imageList[$key]['id'])) {
                $image = SupplyRequestImage::find($this->imageList[$key]['id']);
                if ($image) {
                    $image->deleteFile();
                    $image->delete();
                }
            }
            unset($this->imageList[$key]);
            $this->imageList = array_values($this->imageList);
            $this->dispatch('success', ['message' => 'Successfully deleted image']);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function addCart(): void
    {
        try {
            if (! $this->item['branch_id']) {
                throw new Exception('Please select a store');
            }
            if (! $this->item['product_id']) {
                throw new Exception('Please select an asset/product');
            }

            $branch = Branch::find($this->item['branch_id']);
            $product = Product::find($this->item['product_id']);

            $this->item['branch_name'] = $branch?->name ?? '';
            $this->item['product_name'] = $product?->name ?? '';
            $this->item['edit_flag'] = false;

            $this->items[] = $this->item;
            $savedItem = $this->item;
            $this->initItem();
            $this->mainCalculator();
            $this->item['branch_id'] = $savedItem['branch_id'];

            $this->dispatch('success', ['message' => 'Successfully added to cart']);
            if ($savedItem['barcode']) {
                $this->dispatch('barcodeAutofocus');
            } else {
                $this->dispatch('openProductSelectBox');
            }
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function addNote(): void
    {
        if ($this->note) {
            $this->notes[] = [
                'note' => $this->note,
                'created_by' => Auth::id(),
                'creator' => Auth::user()->name,
                'created_at' => now(),
                'delete_flag' => true,
            ];
            $this->note = '';
        }
    }

    public function deleteNote($key): void
    {
        unset($this->notes[$key]);
        $this->notes = array_values($this->notes);
    }

    public function save()
    {
        $this->validate();
        try {
            if (empty($this->items)) {
                throw new Exception('Please add at least one item');
            }

            DB::beginTransaction();
            $response = (new CreateUpdateAction())->execute(
                $this->supply_request,
                $this->items,
                $this->images ?? [],
                $this->notes,
                Auth::id(),
                $this->table_id
            );
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            DB::commit();

            if ($response['is_create']) {
                $this->dispatch('success', ['message' => $response['message']]);

                $editRoute = $this->type === 'Return' ? 'supply-return::edit' : 'supply-request::edit';

                return $this->redirectRoute($editRoute, $response['data']->id);
            }

            $this->table_id = $response['data']->id;
            $this->mount($this->table_id);
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function statusChange($status): void
    {
        try {
            if ($status === SupplyRequestStatus::COMPLETED->value && ! $this->payment_mode_id) {
                throw new Exception('Please select a payment mode before completing');
            }

            DB::beginTransaction();
            $response = (new StatusChangeAction())->execute($this->table_id, $status, Auth::id(), $this->payment_mode_id ?: null);
            if (! $response['success']) {
                throw new Exception($response['message']);
            }

            DB::commit();

            $this->mount($this->table_id);
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.supply-request.create');
    }
}
