<?php

namespace App\Livewire\Maintenance;

use App\Actions\Maintenance\Complaint\AddAttachmentsAction;
use App\Actions\Maintenance\Complaint\AddNoteAction;
use App\Actions\Maintenance\Complaint\AddSupplyItemAction;
use App\Actions\Maintenance\Complaint\CompleteAction;
use App\Actions\Maintenance\Complaint\DeleteAttachmentAction;
use App\Actions\Maintenance\Complaint\DeleteNoteAction;
use App\Actions\Maintenance\Complaint\DeleteSupplyItemAction;
use App\Actions\Maintenance\Complaint\SaveRemarkAction;
use App\Actions\Maintenance\Complaint\UpdateSupplyItemAction;
use App\Models\Branch;
use App\Models\MaintenanceComplaint;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Complaint extends Component
{
    use WithFileUploads;

    public $complaint_id;

    public $maintenanceComplaint;

    // Info panels
    public $propertyInfo = [];

    public $customerInfo = [];

    public $activityLog = [];

    // Technician remark
    public $technician_remark = '';

    // All sibling complaints
    public $allComplaints = [];

    // Supply Request — shared with partial
    public $supply_request = [];

    public $items = [];

    public $item = [];

    public $images = [];

    public $imageList = [];

    public $note = '';

    public $notes = [];

    public $branches = [];

    public $status = '';

    // Status flags
    public $isCompleted = false;

    public $isCancelled = false;

    public function mount($id)
    {
        $this->complaint_id = $id;
        $this->branches = Branch::pluck('name', 'id');
        $this->initItem();
        $this->loadData();
    }

    public function initItem(): void
    {
        $this->item = [
            'branch_id' => session('branch_id'),
            'barcode' => '',
            'product_id' => null,
            'mode' => 'New',
            'quantity' => 1,
            'unit_price' => 0,
            'total' => 0,
            'remarks' => '',
        ];
    }

    public function loadData()
    {
        $mc = MaintenanceComplaint::with([
            'maintenance.property.building.group',
            'maintenance.property.type',
            'maintenance.rentOut.customer',
            'maintenance.customer',
            'maintenance.creator',
            'maintenance.updater',
            'maintenance.completedBy',
            'maintenance.maintenanceComplaints.complaint.category',
            'maintenance.maintenanceComplaints.technician',
            'complaint.category',
            'technician',
            'assignedBy',
            'completedBy',
            'creator',
            'supplyRequest.items.product',
            'supplyRequest.items.branch',
            'supplyRequest.notes.creator',
            'supplyRequest.images',
        ])->find($this->complaint_id);

        if (! $mc) {
            session()->flash('error', 'Maintenance complaint not found.');

            return redirect()->route('property::maintenance::index');
        }

        $this->maintenanceComplaint = $mc;
        $this->technician_remark = $mc->technician_remark ?? '';
        $this->isCompleted = $mc->status?->value === 'completed';
        $this->isCancelled = $mc->status?->value === 'cancelled';
        $this->status = $mc->status?->value ?? 'pending';

        $maintenance = $mc->maintenance;
        $property = $maintenance?->property;
        $rentOut = $maintenance?->rentOut;
        $customer = $maintenance?->customer ?? $rentOut?->customer;

        // Property Information
        $this->propertyInfo = [
            'registration_id' => $maintenance?->id,
            'group' => $property?->building?->group?->name ?? '',
            'building' => $property?->building?->name ?? '',
            'type' => $property?->type?->name ?? '',
            'property_number' => $property?->number ?? '',
            'priority' => $maintenance?->priority?->label() ?? '',
            'priority_color' => $maintenance?->priority?->color() ?? 'secondary',
            'date' => $maintenance?->date?->format('d-m-Y') ?? '',
            'time' => $maintenance?->time ?? '',
        ];

        // Supply Request data
        $supplyRequest = $mc->supplyRequest;
        $this->supply_request = $supplyRequest ? $supplyRequest->toArray() : [
            'total' => 0,
            'other_charges' => 0,
            'grand_total' => 0,
        ];

        // Customer & Request Information
        $this->customerInfo = [
            'complaint_status' => $mc->status?->label() ?? 'Pending',
            'complaint_status_color' => $mc->status?->color() ?? 'warning',
            'rentout_id' => $rentOut?->id ?? '',
            'rentout_status' => $rentOut?->status?->label() ?? '',
            'agreement_start_date' => $rentOut?->start_date?->format('d-m-Y') ?? '',
            'customer_name' => $customer?->name ?? '',
            'customer_mobile' => $customer?->mobile ?? $maintenance?->contact_no ?? '',
            'work_order_no' => $supplyRequest ? ($supplyRequest->order_no ?? $supplyRequest->id) : 'Not assigned',
        ];

        // Activity Log
        $this->activityLog = [
            'created_by' => $mc->creator?->name ?? '',
            'created_at' => $mc->created_at?->format('d-m-Y h:i:s A') ?? '',
            'assigned_by' => $mc->assignedBy?->name ?? '',
            'assigned_at' => $mc->assigned_at?->format('d-m-Y h:i:s A') ?? '',
            'completed_by' => $mc->completedBy?->name ?? '',
            'completed_at' => $mc->completed_at?->format('d-m-Y h:i:s A') ?? '',
        ];

        // All sibling maintenance complaints
        $this->allComplaints = $maintenance?->maintenanceComplaints->map(function ($item) {
            return [
                'id' => $item->id,
                'category_name' => $item->complaint?->category?->name ?? '',
                'complaint_name' => $item->complaint?->name ?? '',
                'technician_name' => $item->technician?->name ?? '',
                'technician_remark' => $item->technician_remark ?? '',
                'status' => $item->status?->value ?? 'pending',
                'status_label' => $item->status?->label() ?? 'Pending',
                'status_color' => $item->status?->color() ?? 'warning',
                'is_current' => $item->id === $this->complaint_id,
            ];
        })->toArray() ?? [];

        // Supply Items
        $this->items = [];
        if ($supplyRequest) {
            foreach ($supplyRequest->items as $value) {
                $single = $value->toArray();
                $single['branch_name'] = $value->branch?->name ?? 'Main Store';
                $single['product_name'] = $value->product?->name ?? '';
                $single['edit_flag'] = false;
                $this->items[] = $single;
            }
        }

        // Notes
        $this->notes = [];
        if ($supplyRequest) {
            foreach ($supplyRequest->notes()->with('creator')->get() as $value) {
                $this->notes[] = [
                    'id' => $value->id,
                    'note' => $value->note,
                    'creator' => $value->creator?->name ?? '',
                    'created_at' => $value->created_at,
                    'delete_flag' => true,
                ];
            }
        }

        // Images
        $this->imageList = [];
        if ($supplyRequest) {
            foreach ($supplyRequest->images as $value) {
                $relativePath = str_replace('/storage/', '', $value->path);
                $this->imageList[] = [
                    'id' => $value->id,
                    'path' => asset($value->path),
                    'name' => $value->name,
                    'type' => $value->type,
                    'is_video' => $value->is_video,
                    'is_pdf' => $value->is_pdf,
                    'is_image' => str_contains($value->type ?? '', 'image'),
                    'file_exists' => Storage::disk('public')->exists($relativePath),
                ];
            }
        }
    }

    // ── Methods expected by the shared partial ──

    public function updated($key, $value): void
    {
        if ($key === 'item.product_id' && $value) {
            $product = Product::find($value);
            if ($product) {
                $this->item['unit_price'] = $product->cost ?? 0;
                $this->item['total'] = $this->item['unit_price'] * $this->item['quantity'];
            }
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

    public function addCart(): void
    {
        $response = (new AddSupplyItemAction())->execute($this->complaint_id, [
            'branch_id' => $this->item['branch_id'],
            'product_id' => $this->item['product_id'],
            'mode' => $this->item['mode'] ?? 'New',
            'quantity' => $this->item['quantity'] ?? 1,
            'unit_price' => $this->item['unit_price'] ?? null,
            'remarks' => $this->item['remarks'] ?? '',
        ], Auth::id());

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $savedItem = $this->item;
        $this->initItem();
        $this->item['branch_id'] = $savedItem['branch_id'];

        $this->loadData();
        $this->dispatch('success', ['message' => $response['message']]);
        if ($savedItem['barcode']) {
            $this->dispatch('barcodeAutofocus');
        } else {
            $this->dispatch('openProductSelectBox');
        }
    }

    public function editCartItem($key): void
    {
        $this->items[$key]['edit_flag'] = ! $this->items[$key]['edit_flag'];

        // If toggling off (saving), persist to DB via the shared action.
        if (! $this->items[$key]['edit_flag'] && isset($this->items[$key]['id'])) {
            $response = (new UpdateSupplyItemAction())->execute($this->items[$key]['id'], [
                'quantity' => $this->items[$key]['quantity'],
                'unit_price' => $this->items[$key]['unit_price'],
                'remarks' => $this->items[$key]['remarks'] ?? '',
                'branch_id' => $this->items[$key]['branch_id'] ?? null,
            ]);

            if (! $response['success']) {
                $this->dispatch('error', ['message' => $response['message']]);
            }
        }

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
        abort_unless(auth()->user()?->can('supply request.delete item'), 403);

        if (isset($this->items[$key]['id'])) {
            $response = (new DeleteSupplyItemAction())->execute($this->items[$key]['id']);
            if (! $response['success']) {
                $this->dispatch('error', ['message' => $response['message']]);

                return;
            }
        }

        unset($this->items[$key]);
        $this->items = array_values($this->items);
        $this->mainCalculator();
        $this->dispatch('success', ['message' => 'Successfully deleted item']);
    }

    public function deleteImage($key): void
    {
        if (! isset($this->imageList[$key]['id'])) {
            return;
        }

        $response = (new DeleteAttachmentAction())->execute($this->imageList[$key]['id']);
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        unset($this->imageList[$key]);
        $this->imageList = array_values($this->imageList);
        $this->dispatch('success', ['message' => $response['message']]);
    }

    public function addNote(): void
    {
        if (! $this->note) {
            return;
        }

        $response = (new AddNoteAction())->execute($this->complaint_id, $this->note, Auth::id());
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->note = '';
        $this->loadData();
    }

    public function deleteNote($key): void
    {
        if (isset($this->notes[$key]['id'])) {
            $response = (new DeleteNoteAction())->execute($this->notes[$key]['id']);
            if (! $response['success']) {
                $this->dispatch('error', ['message' => $response['message']]);

                return;
            }
        }

        unset($this->notes[$key]);
        $this->notes = array_values($this->notes);
    }

    public function updatedImages(): void
    {
        $response = (new AddAttachmentsAction())->execute($this->complaint_id, $this->images, Auth::id());
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->images = [];
        $this->loadData();
        $this->dispatch('success', ['message' => $response['message']]);
    }

    // ── Save / Complete ──

    public function save($status = 'pending')
    {
        if ($status === 'completed' && empty(trim($this->technician_remark))) {
            $this->addError('technician_remark', 'The technician remark is required to complete.');

            return;
        }

        $response = $status === 'completed'
            ? (new CompleteAction())->execute($this->complaint_id, Auth::id(), $this->technician_remark)
            : (new SaveRemarkAction())->execute($this->complaint_id, $this->technician_remark, Auth::id());

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->dispatch('success', ['message' => $status === 'completed' ? 'Complaint completed successfully.' : 'Saved successfully.']);
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.maintenance.complaint');
    }
}
