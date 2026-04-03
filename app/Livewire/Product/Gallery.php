<?php

namespace App\Livewire\Product;

use App\Actions\Product\DeleteImageAction;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Gallery extends Component
{
    use WithPagination;

    public $search = '';

    public $department_id = '';

    public $main_category_id = '';

    public $brand_id = '';

    public $image_type = '';

    public $selected = [];

    public $selectAll = false;

    public $limit = 48;

    public $previewImage = null;

    public $previewProductName = null;

    protected $paginationTheme = 'bootstrap';

    public function updated($key, $value)
    {
        if (! in_array($key, ['selectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getFilteredQuery()
                ->limit(2000)
                ->pluck('product_images.id')
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function setPreview($imagePath, $productName)
    {
        $this->previewImage = $imagePath;
        $this->previewProductName = $productName;
    }

    public function closePreview()
    {
        $this->previewImage = null;
        $this->previewProductName = null;
    }

    public function deleteSelected()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select at least one image to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteImageAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            $this->dispatch('success', ['message' => 'Successfully deleted '.count($this->selected).' images']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    protected function getFilteredQuery()
    {
        return ProductImage::query()
            ->join('products', 'product_images.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->when($this->search ?? '', function ($query, $value) {
                $value = trim($value);

                return $query->where(function ($q) use ($value) {
                    $q->where('products.name', 'like', "%{$value}%")
                        ->orWhere('products.code', 'like', "%{$value}%")
                        ->orWhere('products.barcode', 'like', "%{$value}%");
                });
            })
            ->when($this->department_id ?? '', function ($query, $value) {
                return $query->where('products.department_id', $value);
            })
            ->when($this->main_category_id ?? '', function ($query, $value) {
                return $query->where('products.main_category_id', $value);
            })
            ->when($this->brand_id ?? '', function ($query, $value) {
                return $query->where('products.brand_id', $value);
            })
            ->when($this->image_type ?? '', function ($query, $value) {
                return $query->where('product_images.method', $value);
            })
            ->select([
                'product_images.*',
                'products.name as product_name',
                'products.code as product_code',
                'products.thumbnail as product_thumbnail',
            ]);
    }

    public function render()
    {
        $data = $this->getFilteredQuery()
            ->orderBy('product_images.created_at', 'desc')
            ->paginate($this->limit);

        return view('livewire.product.gallery', [
            'data' => $data,
        ]);
    }
}
