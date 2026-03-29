<?php

namespace App\Livewire\SupplyRequest;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Property;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestImage;
use App\Models\SupplyRequestItem;
use App\Models\SupplyRequestNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                'order_no' => SupplyRequest::getNextOrderNo(),
                'contact_person' => '',
                'total' => 0,
                'other_charges' => 0,
                'grand_total' => 0,
                'status' => \App\Enums\SupplyRequest\SupplyRequestStatus::REQUIREMENT->value,
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
            $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath);

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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function addCart(): void
    {
        try {
            if (! $this->item['branch_id']) {
                throw new \Exception('Please select a store');
            }
            if (! $this->item['product_id']) {
                throw new \Exception('Please select an asset/product');
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
        } catch (\Exception $e) {
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

    public function save(): void
    {
        $this->validate();
        try {
            DB::beginTransaction();

            if (empty($this->items)) {
                throw new \Exception('Please add at least one item');
            }

            $data = $this->supply_request;
            $data['updated_by'] = Auth::id();

            if ($this->table_id) {
                $model = SupplyRequest::findOrFail($this->table_id);
                $model->update($data);
            } else {
                $data['created_by'] = Auth::id();
                $data['tenant_id'] = Auth::user()->tenant_id;
                $data['branch_id'] = session('branch_id', 1);
                $data['order_no'] = SupplyRequest::getNextOrderNo();
                $model = SupplyRequest::create($data);
            }

            // Save items
            foreach ($this->items as $itemData) {
                if (isset($itemData['id'])) {
                    $item = SupplyRequestItem::find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'branch_id' => $itemData['branch_id'],
                            'product_id' => $itemData['product_id'],
                            'mode' => $itemData['mode'],
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'remarks' => $itemData['remarks'] ?? null,
                        ]);
                    }
                } else {
                    SupplyRequestItem::create([
                        'supply_request_id' => $model->id,
                        'branch_id' => $itemData['branch_id'],
                        'product_id' => $itemData['product_id'],
                        'mode' => $itemData['mode'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'remarks' => $itemData['remarks'] ?? null,
                    ]);
                }
            }

            // Save images
            if ($this->images) {
                foreach ($this->images as $file) {
                    $imageModel = new SupplyRequestImage();
                    $result = $imageModel->storeFile($file, $model->id);
                    if ($result['success']) {
                        SupplyRequestImage::create([
                            'supply_request_id' => $model->id,
                            'name' => $result['fileName'],
                            'path' => $result['path'],
                            'type' => $result['type'],
                        ]);
                    }
                }
            }

            // Save notes
            foreach ($this->notes as $noteData) {
                if (! isset($noteData['id'])) {
                    SupplyRequestNote::create([
                        'supply_request_id' => $model->id,
                        'note' => $noteData['note'],
                        'created_by' => $noteData['created_by'],
                    ]);
                }
            }

            // Update totals
            $model->update([
                'total' => $this->supply_request['total'],
                'other_charges' => $this->supply_request['other_charges'] ?? 0,
                'grand_total' => $this->supply_request['grand_total'],
            ]);

            DB::commit();

            $this->table_id = $model->id;
            $this->mount($this->table_id);

            $link = route('supply-request::edit', $model->id);
            $this->dispatch('success', ['message' => "<a href='{$link}'>Successfully saved</a>"]);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function statusChange($status): void
    {
        try {
            DB::beginTransaction();
            $model = SupplyRequest::findOrFail($this->table_id);
            $data = ['status' => $status];

            $statusEnum = \App\Enums\SupplyRequest\SupplyRequestStatus::from($status);
            switch ($statusEnum) {
                case \App\Enums\SupplyRequest\SupplyRequestStatus::APPROVED:
                case \App\Enums\SupplyRequest\SupplyRequestStatus::REJECTED:
                    $data['approved_by'] = Auth::id();
                    $data['approved_at'] = now();
                    break;
                case \App\Enums\SupplyRequest\SupplyRequestStatus::FINAL_APPROVED:
                    $data['final_approved_by'] = Auth::id();
                    $data['final_approved_at'] = now();
                    break;
                case \App\Enums\SupplyRequest\SupplyRequestStatus::COMPLETED:
                    $data['completed_by'] = Auth::id();
                    $data['completed_at'] = now();
                    break;
            }

            $model->update($data);
            DB::commit();

            $this->mount($this->table_id);
            $this->dispatch('success', ['message' => 'Status updated successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.supply-request.create');
    }
}
