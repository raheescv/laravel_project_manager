<?php

namespace App\Actions\Issue;

use App\Models\Issue;

class GetAction
{
    public function execute(int $id): ?Issue
    {
        return Issue::with([
            'account:id,name,mobile',
            'sourceIssue:id,date',
            'items.product:id,name,code',
            'items.inventory:id,product_id,barcode,batch',
            'items.sourceIssueItem:id,issue_id',
        ])
            ->find($id);
    }
}
