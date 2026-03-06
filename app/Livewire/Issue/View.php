<?php

namespace App\Livewire\Issue;

use App\Models\Issue;
use Livewire\Component;

class View extends Component
{
    public ?int $table_id = null;

    public ?Issue $model = null;

    public function mount(?int $table_id = null): void
    {
        $this->table_id = $table_id;
        $this->model = Issue::with([
            'account:id,name,mobile',
            'sourceIssue:id,date',
            'items' => fn ($q) => $q->with(
                'product:id,name,code',
                'inventory:id,product_id,barcode,batch',
                'sourceIssueItem:id,issue_id,inventory_id,product_id,quantity_in,quantity_out',
                'sourceIssueItem.issue:id,type,date',
                'sourceIssueItem.product:id,name,code',
                'returnedItems:id,issue_id,source_issue_item_id,quantity_in',
                'returnedItems.issue:id,type,date'
            )->orderBy('id', 'asc'),
        ])->find($this->table_id);
        if (! $this->model) {
            $this->redirect(route('issue::index'));
        }
    }

    public function render()
    {
        return view('livewire.issue.view');
    }
}
