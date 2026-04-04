<?php

namespace App\Livewire\Maintenance;

use App\Enums\Maintenance\MaintenanceComplaintStatus;
use App\Models\Branch;
use App\Models\MaintenanceComplaint;
use App\Models\Product;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestImage;
use App\Models\SupplyRequestItem;
use App\Models\SupplyRequestNote;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    // ── Supply Request: Create or get ──

    private function getOrCreateSupplyRequest()
    {
        $mc = MaintenanceComplaint::with('maintenance')->find($this->complaint_id);

        if ($mc->supply_request_id) {
            return SupplyRequest::find($mc->supply_request_id);
        }

        $maintenance = $mc->maintenance;
        $sr = SupplyRequest::create([
            'tenant_id' => $mc->tenant_id,
            'branch_id' => $mc->branch_id,
            'property_id' => $maintenance->property_id,
            'date' => now()->format('Y-m-d'),
            'type' => 'Add',
            'status' => 'requirement',
            'total' => 0,
            'other_charges' => 0,
            'grand_total' => 0,
            'created_by' => Auth::id(),
        ]);

        $mc->supply_request_id = $sr->id;
        $mc->save();

        return $sr;
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
        try {
            if (! $this->item['branch_id']) {
                throw new Exception('Please select a store');
            }
            if (! $this->item['product_id']) {
                throw new Exception('Please select an asset/product');
            }

            DB::beginTransaction();
            $sr = $this->getOrCreateSupplyRequest();

            $branch = Branch::find($this->item['branch_id']);
            $product = Product::find($this->item['product_id']);

            $this->item['branch_name'] = $branch?->name ?? '';
            $this->item['product_name'] = $product?->name ?? '';
            $this->item['edit_flag'] = false;

            // Save to DB immediately
            SupplyRequestItem::create([
                'supply_request_id' => $sr->id,
                'product_id' => $this->item['product_id'],
                'branch_id' => $this->item['branch_id'],
                'mode' => $this->item['mode'] ?? 'New',
                'quantity' => $this->item['quantity'] ?? 1,
                'unit_price' => $this->item['unit_price'] ?? 0,
                'total' => $this->item['total'] ?? 0,
                'remarks' => $this->item['remarks'] ?? '',
            ]);

            $this->updateSupplyTotals($sr);
            DB::commit();

            $savedItem = $this->item;
            $this->initItem();
            $this->item['branch_id'] = $savedItem['branch_id'];

            $this->loadData();
            $this->dispatch('success', ['message' => 'Successfully added to cart']);
            if ($savedItem['barcode']) {
                $this->dispatch('barcodeAutofocus');
            } else {
                $this->dispatch('openProductSelectBox');
            }
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function editCartItem($key): void
    {
        $this->items[$key]['edit_flag'] = ! $this->items[$key]['edit_flag'];

        // If toggling off (saving), persist to DB
        if (! $this->items[$key]['edit_flag'] && isset($this->items[$key]['id'])) {
            $item = SupplyRequestItem::find($this->items[$key]['id']);
            if ($item) {
                $item->update([
                    'quantity' => $this->items[$key]['quantity'],
                    'unit_price' => $this->items[$key]['unit_price'],
                    'total' => $this->items[$key]['total'],
                    'remarks' => $this->items[$key]['remarks'] ?? '',
                    'branch_id' => $this->items[$key]['branch_id'] ?? $item->branch_id,
                ]);
                $this->updateSupplyTotals($item->supplyRequest);
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
        try {
            DB::beginTransaction();
            if (isset($this->items[$key]['id'])) {
                $item = SupplyRequestItem::find($this->items[$key]['id']);
                if ($item) {
                    $sr = $item->supplyRequest;
                    $item->delete();
                    $this->updateSupplyTotals($sr);
                }
            }
            unset($this->items[$key]);
            $this->items = array_values($this->items);
            $this->mainCalculator();
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully deleted item']);
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
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully deleted image']);
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function addNote(): void
    {
        if (! $this->note) {
            return;
        }

        try {
            DB::beginTransaction();
            $sr = $this->getOrCreateSupplyRequest();

            SupplyRequestNote::create([
                'supply_request_id' => $sr->id,
                'note' => $this->note,
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            $this->note = '';
            $this->loadData();
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function deleteNote($key): void
    {
        try {
            if (isset($this->notes[$key]['id'])) {
                SupplyRequestNote::destroy($this->notes[$key]['id']);
            }
            unset($this->notes[$key]);
            $this->notes = array_values($this->notes);
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updatedImages(): void
    {
        try {
            DB::beginTransaction();
            $sr = $this->getOrCreateSupplyRequest();

            foreach ($this->images as $file) {
                $image = new SupplyRequestImage();
                $image->supply_request_id = $sr->id;
                $image->name = $file->getClientOriginalName();
                $image->type = $file->getClientMimeType();
                $image->path = $image->storeFile($file, $sr->id);
                $image->save();
            }

            DB::commit();
            $this->images = [];
            $this->loadData();
            $this->dispatch('success', ['message' => 'Files uploaded successfully.']);
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    private function updateSupplyTotals($sr): void
    {
        $sr->total = $sr->items()->sum('total');
        $sr->grand_total = $sr->total + ($sr->other_charges ?? 0);
        $sr->save();
    }

    // ── Save / Complete ──

    public function save($status = 'pending')
    {
        try {
            if ($status === 'completed' && empty(trim($this->technician_remark))) {
                $this->addError('technician_remark', 'The technician remark is required to complete.');

                return;
            }

            DB::beginTransaction();

            $mc = MaintenanceComplaint::find($this->complaint_id);
            $mc->technician_remark = $this->technician_remark;
            $mc->updated_by = Auth::id();

            if ($status === 'completed') {
                $mc->status = MaintenanceComplaintStatus::Completed;
                $mc->completed_by = Auth::id();
                $mc->completed_at = now();
            }

            $mc->save();

            // Auto-complete maintenance if all complaints done
            if ($status === 'completed') {
                $maintenance = $mc->maintenance;
                $allDone = $maintenance->maintenanceComplaints()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count() === 0;

                if ($allDone) {
                    $maintenance->status = 'completed';
                    $maintenance->completed_by = Auth::id();
                    $maintenance->completed_at = now();
                    $maintenance->save();
                }
            }

            DB::commit();
            $this->dispatch('success', ['message' => $status === 'completed' ? 'Complaint completed successfully.' : 'Saved successfully.']);
            $this->loadData();
        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.maintenance.complaint');
    }
}
